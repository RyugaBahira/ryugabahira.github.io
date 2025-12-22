<?php
/**
 * REST API untuk Manajemen Ebook dengan Basic Authentication
 * 
 * FITUR KEAMANAN:
 * - Semua endpoint (GET, POST, PUT) memerlukan Basic Authentication
 * - Request tanpa autentikasi mengembalikan HTTP 401
 * - Kredensial diverifikasi sebelum memproses request
 * 
 * CARA HOSTING DI GITHUB:
 * 1. Buat repository baru di GitHub
 * 2. Upload file api.php dan data.json ke repository
 * 3. Gunakan GitHub Pages atau hosting PHP seperti:
 *    - InfinityFree (gratis)
 *    - 000webhost (gratis)
 *    - Heroku dengan PHP buildpack
 * 4. Update URL di aplikasi Android dengan URL hosting Anda
 * 
 * CONTOH URL: https://ryugabahira.github.io/api/api_quran_muslim.php
 * 
 * CARA MENGUBAH KREDENSIAL:
 * Edit konstanta AUTH_USERNAME dan AUTH_PASSWORD di bawah ini
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// KONFIGURASI AUTENTIKASI
// Ubah nilai ini untuk mengubah kredensial
// ============================================
define('AUTH_USERNAME', 'baguss');
define('AUTH_PASSWORD', '$#ryug4b4hir4#$');

// Path ke file data
$dataFile = 'ebook_gratis.json';

/**
 * Fungsi untuk memverifikasi autentikasi Basic Auth
 * 
 * Memeriksa header Authorization dan memvalidasi kredensial
 * Format header: Authorization: Basic base64(username:password)
 * 
 * @return bool True jika autentikasi berhasil, false jika gagal
 */
function authenticate() {
    // Cek apakah header Authorization ada
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        return false;
    }
    
    // Validasi username dan password
    return $_SERVER['PHP_AUTH_USER'] === AUTH_USERNAME && 
           $_SERVER['PHP_AUTH_PW'] === AUTH_PASSWORD;
}

/**
 * Fungsi untuk mengirim response error autentikasi
 */
function sendAuthError() {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Autentikasi gagal. Username atau password salah.'
    ]);
    exit();
}

/**
 * Fungsi untuk membaca data dari file JSON
 */
function readData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

/**
 * Fungsi untuk menulis data ke file JSON
 */
function writeData($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Fungsi untuk mengirim response JSON
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// ============================================
// ROUTING BERDASARKAN METHOD HTTP
// Semua endpoint memerlukan autentikasi
// ============================================
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // GET: Mengambil semua data ebook (MEMERLUKAN AUTENTIKASI)
        if (!authenticate()) {
            sendAuthError();
        }
        
        $data = readData($dataFile);
        sendResponse([
            'success' => true,
            'data' => $data
        ]);
        break;
        
    case 'POST':
        // POST: Menambah ebook baru (MEMERLUKAN AUTENTIKASI)
        if (!authenticate()) {
            sendAuthError();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['judul']) || !isset($input['penulis'])) {
            sendResponse([
                'success' => false,
                'message' => 'Data tidak lengkap'
            ], 400);
        }
        
        $data = readData($dataFile);
        
        // Generate ID baru
        $newId = count($data) > 0 ? max(array_column($data, 'id')) + 1 : 1;
        
        $newEbook = [
            'id' => $newId,
            'judul' => htmlspecialchars($input['judul']),
            'penulis' => htmlspecialchars($input['penulis']),
            'deskripsi' => htmlspecialchars($input['deskripsi'] ?? ''),
            'kategori' => htmlspecialchars($input['kategori'] ?? 'Umum'),
            'status' => $input['status'] ?? true,
            'createdAt' => time(),
            'updatedAt' => time()
        ];
        
        $data[] = $newEbook;
        writeData($dataFile, $data);
        
        sendResponse([
            'success' => true,
            'message' => 'Ebook berhasil ditambahkan',
            'data' => $newEbook
        ], 201);
        break;
        
    case 'PUT':
        // PUT: Update ebook yang ada (MEMERLUKAN AUTENTIKASI)
        if (!authenticate()) {
            sendAuthError();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id'])) {
            sendResponse([
                'success' => false,
                'message' => 'ID ebook tidak ditemukan'
            ], 400);
        }
        
        $data = readData($dataFile);
        $found = false;
        
        foreach ($data as $key => $ebook) {
            if ($ebook['id'] == $input['id']) {
                $data[$key] = [
                    'id' => $ebook['id'],
                    'judul' => htmlspecialchars($input['judul'] ?? $ebook['judul']),
                    'penulis' => htmlspecialchars($input['penulis'] ?? $ebook['penulis']),
                    'deskripsi' => htmlspecialchars($input['deskripsi'] ?? $ebook['deskripsi']),
                    'kategori' => htmlspecialchars($input['kategori'] ?? $ebook['kategori']),
                    'status' => $input['status'] ?? $ebook['status'],
                    'createdAt' => $ebook['createdAt'],
                    'updatedAt' => time()
                ];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            sendResponse([
                'success' => false,
                'message' => 'Ebook tidak ditemukan'
            ], 404);
        }
        
        writeData($dataFile, $data);
        
        sendResponse([
            'success' => true,
            'message' => 'Ebook berhasil diperbarui',
            'data' => $data[$key]
        ]);
        break;
        
    default:
        sendResponse([
            'success' => false,
            'message' => 'Method tidak didukung'
        ], 405);
}
?>