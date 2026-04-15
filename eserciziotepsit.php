<?php
header("Content-Type: application/json");

// Connessione al database (modifica con i tuoi dati)
$host = "localhost";
$db = "libreria";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);

// Controllo connessione
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["errore" => "Connessione al database fallita"]);
    exit;
}

// Metodo HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Recupero ID se presente
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Legge JSON dal body
$input = json_decode(file_get_contents("php://input"), true);

// Funzione per validare input
function validaLibro($data) {
    return isset($data['id'], $data['titolo'], $data['autore'], $data['anno']) &&
           is_int($data['id']) &&
           is_string($data['titolo']) &&
           is_string($data['autore']) &&
           is_int($data['anno']);
}

// SWITCH principale
switch ($method) {

    // ================= GET =================
    case "GET":
        $sql = "SELECT * FROM libri";
        $result = $conn->query($sql);

        $libri = [];
        while ($row = $result->fetch_assoc()) {
            $libri[] = $row;
        }

        echo json_encode($libri);
        break;

    // ================= POST =================
    case "POST":
        if (!validaLibro($input)) {
            http_response_code(400);
            echo json_encode(["errore" => "Dati non validi"]);
            break;
        }

        $stmt = $conn->prepare("INSERT INTO libri (id, titolo, autore, anno) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi",
            $input['id'],
            $input['titolo'],
            $input['autore'],
            $input['anno']
        );

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode($input);
        } else {
            http_response_code(500);
            echo json_encode(["errore" => "Errore inserimento"]);
        }

        break;

    // ================= DELETE =================
    case "DELETE":
        if (!$id) {
            http_response_code(400);
            echo json_encode(["errore" => "ID mancante"]);
            break;
        }

        // Controllo esistenza
        $check = $conn->prepare("SELECT id FROM libri WHERE id=?");
        $check->bind_param("i", $id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["errore" => "Libro non trovato"]);
            break;
        }

        $stmt = $conn->prepare("DELETE FROM libri WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(["messaggio" => "Libro eliminato"]);
        break;

    // ================= PUT =================
    case "PUT":
        if (!$id) {
            http_response_code(400);
            echo json_encode(["errore" => "ID mancante"]);
            break;
        }

        if (!isset($input['titolo'], $input['autore'], $input['anno'])) {
            http_response_code(400);
            echo json_encode(["errore" => "Dati incompleti"]);
            break;
        }

        // Controllo esistenza
        $check = $conn->prepare("SELECT id FROM libri WHERE id=?");
        $check->bind_param("i", $id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["errore" => "Libro non trovato"]);
            break;
        }

        $stmt = $conn->prepare("UPDATE libri SET titolo=?, autore=?, anno=? WHERE id=?");
        $stmt->bind_param("ssii",
            $input['titolo'],
            $input['autore'],
            $input['anno'],
            $id
        );

        if ($stmt->execute()) {
            echo json_encode(["messaggio" => "Libro aggiornato"]);
        } else {
            http_response_code(500);
            echo json_encode(["errore" => "Errore aggiornamento"]);
        }

        break;

    // ================= ERRORE =================
    default:
        http_response_code(405);
        echo json_encode(["errore" => "Metodo non consentito"]);
        break;
}

$conn->close();
?>