<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', '512M'); // Zvyšuje limit paměti na 512 MB

// Nastavení připojení k databázi
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "users";

// Načtení TCPDF a FPDI pomocí Composeru
require_once 'fpdi/src/autoload.php';
require_once('tcpdf/tcpdf.php'); 
// Import FPDI pro TCPDF
use setasign\Fpdi\Tcpdf\Fpdi; // Toto je klíčový import

// Vytvoření připojení pomocí PDO   
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Připojení k databázi bylo úspěšné<br>";
} catch(PDOException $e) {
    echo "Chyba při připojení k databázi: " . $e->getMessage();
    exit;
}

// Nastavení limitu a offsetu pro dávkování dat
$limit = 10;
$offset = 0;

do {
    // Načtení části dat z databáze
    $conn->exec("SET NAMES utf8mb4"); // Zajištění správného kódování UTF-8
    $sql = "SELECT prijmeni, jmeno, cislo_pojistence, kontakni_adresa FROM deti LIMIT $limit OFFSET $offset";
    $stmt = $conn->query($sql);

    // Zpracování výsledků
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Vytvoření nového PDF dokumentu pomocí FPDI
        $pdf = new Fpdi();

        // Nastavení základních metadat pro PDF
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Autor PDF');
        $pdf->SetTitle('Potvrzení');
        $pdf->SetSubject('PDF s českými znaky');
        $pdf->SetKeywords('TCPDF, PDF, PHP, UTF-8, české znaky');

        // Načtení existující šablony PDF pomocí FPDI
        $pdf->setSourceFile('C:/xampp/htdocs/tabor/templates/dokument3.pdf'); // cesta k tvojí šabloně PDF
        $tplIdx = $pdf->importPage(1); // Načtení první stránky šablony
        $pdf->AddPage();
        $pdf->useTemplate($tplIdx, 0, 0, 210); // Aplikování šablony na stránku

        // Nastavení fontu pro české znaky
        $pdf->SetFont('dejavusans', '', 12);

        // Přidání textu do šablony
        $pdf->SetXY(120, 64); // Nastavení pozice pro jméno a příjmení
        $pdf->Write(0, $row["jmeno"] . " " . $row["prijmeni"]);

        $pdf->SetXY(120, 71); // Nastavení pozice pro číslo pojištěnce
        $pdf->Write(0, substr($row["cislo_pojistence"], 0, 12));

        $pdf->SetXY(120, 78); // Nastavení pozice pro kontaktní adresu
        $pdf->Write(0, $row["kontakni_adresa"]);

        $pdf->SetXY(30, 135); // Nastavení pozice pro kontaktní adresu
        $kontakni_adresa = "Jitřenka Bučovice, Žandovský mlýn, 345 Archlebov, 696 33";
        $pdf->MultiCell(120, 10, $kontakni_adresa, 0, 'L', 0);  // MultiCell s šířkou 60, výškou 10, zarovnáním doleva


        // Název souboru
        $directory = 'C:/xampp/htdocs/tabor/';
        $jmeno_souboru = $directory . str_replace(' ', '_', $row["prijmeni"] . "_" . $row["jmeno"] . "_RBP". ".pdf");

        // Uložení PDF souboru
        $pdf->Output($jmeno_souboru, 'F');
        echo "Soubor $jmeno_souboru byl úspěšně vytvořen.<br>";
    }

    // Zvyšte offset pro načtení další dávky dat
    $offset += $limit;

} while ($stmt->rowCount() > 0);

// Uzavření připojení
$conn = null;

