<?php
//Uključivanje Simple HTML DOM parsera
include('simplehtmldom_1_9_1/simple_html_dom.php');

//Definicija interfejsa iRadovi
interface iRadovi {
    public function create($data);
    public function save();
    public function read(); 
}

//Klasa DiplomskiRadovi koja implementira interfejs iRadovi
class DiplomskiRadovi implements iRadovi {
    private $naziv_rada = NULL;
    private $tekst_rada = NULL;
    private $link_rada = NULL;
    private $oib_tvrtke = NULL;

    //Metoda create služi za postavljanje podataka 
    //Id se postavlja automatski u bazi
    //U phpMyAdmin uključen je AUTO_INCREMENT za id
    public function create($data) {
        $this->naziv_rada = $data['naziv_rada'];
        $this->tekst_rada = $data['tekst_rada'];
        $this->link_rada = $data['link_rada'];
        $this->oib_tvrtke = $data['oib_tvrtke'];
    }

    //Metoda save služi za spremanje podataka u bazu radovi
    public function save() {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "radovi";

        //Stvara se konekcija s bazom podataka
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        //SQL upit za unos podataka
        //Ukoliko su podaci uspješno uneseni, neće se dogoditi ništa
        //Ukoliko su podaci neuspješno uneseni, ispisati će se poruka o greški
        //Na kraju zatvaramo konekciju
        $sql = "INSERT INTO `diplomski_radovi` (`naziv_rada`, `tekst_rada`, `link_rada`, `oib_tvrtke`) VALUES ('$this->naziv_rada', '$this->tekst_rada', '$this->link_rada', '$this->oib_tvrtke')";
        if($conn->query($sql) === true) {
        }
        else {
            echo "Error! " . $sql . "<br>" . $conn->error;
        }
        $conn->close();
    }

    //Metoda read služi za čitanje podataka iz baze
    public function read() {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "radovi";

        //Stvara se konekcija s bazom podataka
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        //SQL upit za čitanje svih podataka iz tablice diplomski_radovi
        //Izvršava se SQL upit, a nakon toga se provjerava jesu li pronađeni podaci
        //Ako jesu, prolazi se kroz rezultate i ispisuje ih se 
        //Ako nisu, ispisuje se poruka da rezultati nisu pronađeni                   
        $sql = "SELECT * FROM `diplomski_radovi`";
        $data = $conn->query($sql);
        if ($data->num_rows > 0) {
            while($row = $data->fetch_assoc()) {
                echo "<h2>ID: " . $row["id"] . "</h2>" .
                "<p>Naslov rada: <a href='" . $row["link_rada"] . "'>" . $row["naziv_rada"] . "</a></p>" .
                "<p>OIB tvrtke: " . $row["oib_tvrtke"] . "</p>";
            }
        } else {
            echo "<p>No results found</p>";
        }
        $conn->close();
    }
}

//Povezivanje na 2. stranicu pomoću cURL-a
$url = 'https://stup.ferit.hr/index.php/zavrsni-radovi/page/2';

//Pokrećemo cURL spoj
//Zaustavlja se ako se dogodi greška
//Dozvoljava se preusmjeravanje
//Spremaju se vraćeni podaci u varijablu te se zatvara spoj
$curl = curl_init($url);  
curl_setopt($curl, CURLOPT_FAILONERROR, 1); 
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
$r = curl_exec($curl);
curl_close($curl);

//Inicijalizacija Simple HTML DOM parsera i parsiranje HTML-a kako bi se dohvatili podaci za svaki članak na stranici
$dom = new simple_html_dom();
$dom->load($r);

//Iteriranje kroz sve članke na stranici
foreach($dom->find('article') as $article) {
    $link = $article->find('h2.entry-title a', 0); 

    //Inicijalizacija cURL-a za dohvaćanje HTML-a pojedinačnog rada
    $curlArticle = curl_init($link->href);
    curl_setopt($curlArticle, CURLOPT_FAILONERROR, 1);
    curl_setopt($curlArticle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curlArticle, CURLOPT_RETURNTRANSFER, 1);
    $html = curl_exec($curlArticle);
    curl_close($curlArticle);

    //Parsiranje HTML-a pojedinačnog rada
    $domArticle = new simple_html_dom();
    $domArticle->load($html);

    //Pronalaženje teksta rada i OIB-a tvrtke
    $text = $domArticle->find('.post-content', 0);
    $image = $article->find('img', 0);
    $oib_tvrtke = preg_replace('/[^0-9]/', '', $image->src);

    //Kreiranje niza s podacima o radu
    $rad = array(
        'naziv_rada' => $link->plaintext,
        'tekst_rada' => ($text) ? $text->plaintext : "", 
        'link_rada' => $link->href,
        'oib_tvrtke' => $oib_tvrtke
    ); 

    //Stvaranje objekta klase DiplomskiRadovi i spremanje podataka
    $diplomski_rad = new DiplomskiRadovi();
    $diplomski_rad->create($rad);
    $diplomski_rad->save();
}

//Čitanje i ispisivanje svih podataka iz baze podataka
$diplomski_rad = new DiplomskiRadovi();
$diplomski_rad->read();
?>
