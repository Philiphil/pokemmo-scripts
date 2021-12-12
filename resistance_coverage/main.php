<?php

require "simple_html_dom.php";
const URL_FICHE = "https://pokestrat.io/fiche-pokemon/";
const POKEDEX = "POKEDEX".DIRECTORY_SEPARATOR;
const EXCLUDED = [
    "latios",
    "latias",
    "mew",
    "mewtwo",
    "raikou",
    "moltres",
    "suicune",
    "ho-oh",
    "lugia",
    "zekrom"
];

const POKEMMO_OU = [
    "hydreigon",
    "scizor",
    "garchomp",
    "mienshao",
    "salamence",
    "magnezone",
    "reuniclus",
    "conkeldurr",
    "excadrill",
    "kindra",
    "pelipper",
    "skarmory",
    "rotom",
    "volcarona",
    "jellicent",
    "gengar",
    "ferrothorn",
    "dugtrio",
    "ludicolo",
    "blissey",
    "togekiss",
    'tyranitar',
    "exeggutor",
    "torkoal",
    "darmanitan",
    "gyarados",
    "chansey",
    "weavile",
    "starmie",
    'cloyster',
    "cofagrigus",
    "serperior",
    "dragonite",
    "hippowdon",
    "breloom",
    "milotic",
    "gliscor",
    "infernape",
    "haxorus",
    "porygon2",
    "tentacruel",
    "lucario",
    "swampert",
    "nidoqueen",
    "mamoswine",
    "roserade",
    "sigilyph",
    "aerodactyl",
    "toxicroak",
    "mandibuzz",
    "donphan",
    "medicharm",
    "gastrodon",
    "marowak",
    "heracross",
    "rotom-laveuse",
    "rotom-chaleur",
    "rotom-tondeuse"

];
const POKEMMO_COMMON = [
   "garchomp",
    "scizor",
    "conkeldurr",
    "tyranitar",
    "gengar",
    "heydreigon",
    "chansey",
    "cofagrigus",
    "pelipper",
    "ferrothorn",
    "kingdra",
    "mienshao",
    "gyrados",
    "volcarona",
    "reuniclus",
    "blissey",
    "graonite",
    "togekiss",
    "skarmory",
    "magnezone",
    "excadrill",



];

//"https://www.smogon.com/dex/bw/formats/uu/";
$ouList =  file_get_html("lists/bw_ou.smogon.html");
$uuList =  file_get_html("lists/bw_uu.smogon.html");
$uublList =  file_get_html("lists/bw_uubl.smogon.html");
//$ruList =  file_get_html("lists/bw_ru.smogon.html");
$rublList =  file_get_html("lists/bw_rubl.smogon.html");

$listTotal = array_merge(
     //getNamesFromSmogon($ouList),
    //getNamesFromSmogon($uuList),
    //getNamesFromSmogon($uublList),
    //getNamesFromSmogon($ruList),
    //getNamesFromSmogon($rublList),
    POKEMMO_OU,POKEMMO_COMMON
);
$listTotal=array_unique($listTotal);

echo json_encode(
    weaknessScorePercentage(makeWeaknessScore($listTotal) ,count($listTotal) )
);

function weaknessScorePercentage(array $scores,int$quantity): array
{
    $total=0;
    foreach ($scores as $score){$total+=$score;}
    $base = $total / count($scores);
    foreach ($scores as $key => $value)
    {
        $scores[$key] = round($value / $quantity,2);
    }
    return $scores;
}
function getNamesFromSmogon($html) :array
{
    $table = $html->find("div.DexTable");
    $lines = $html->find("div.PokemonAltRow");

    $names = [];
    /* @var simple_html_dom $line */
    foreach ($lines as $line)
    {
        /* @var simple_html_dom_node $a */
        $a = $line->find("a")[0];
        $href =  $a->getAttribute("href");
        $cleaned =  substr($href, 0,strripos($href,"/"));
        $cleaned =  substr($cleaned, strripos($cleaned,"/"));
        $cleaned =  substr($cleaned, 1);
        $names[]=$cleaned;
    }
    return $names;
}
function makeWeaknessScore(array &$names) : array
{
    $result = [];
    foreach ($names as $key => $name){
        if(in_array($name,EXCLUDED))continue;

        try {
            $pokemon = getPokemon($name);
            foreach($pokemon as $key => $value){
                if(!isset($result[$key]))$result[$key] =0;
                $result[$key] = (int) $result[$key] + $value;
            }
        }catch(Exception $exception){
            unset($names[$key]);
        }

    }

    asort($result);
    return $result;
}

function getPokemon(string $name) : array
{
    $exists = file_exists(POKEDEX.$name);
    $fiche =  file_get_html($exists?POKEDEX.$name : URL_FICHE . $name);
    //wasting a get is ok, wasting a file is not
    //if(!$exists) file_put_contents(POKEDEX.$name,$fiche);

    $weakness = $fiche->find(".faiblesse");
    if($weakness === null ||count($weakness)===0) throw new Exception("failed");

    //wasting a get is ok, wasting a file is not
    if(!$exists) file_put_contents(POKEDEX.$name,$fiche);

    $weakness = $weakness[0];

    $header = $weakness->find("th");
    $faiblesseHeader = [];
    foreach ($header as $head)
    {
        $head = $head->find("div")[0];
        $faiblesseHeader[] = $head->innertext();
    }

    $body = $weakness->find("td");

    $faiblesse = [];
    foreach ($body as $head)
    {
        switch ($head->innertext()){
            case "¼":
                $faiblesse[] = 0.25;
                break;
            case "½":
                $faiblesse[] = 0.5;
                break;
            default:
                $faiblesse[] = (int)$head->innertext();
        }
    }
    $final = [];
    for ($i=0;$i<count($faiblesse);$i++)
    {
        $final[ $faiblesseHeader[$i] ] =  $faiblesse[$i];
    }

    //TODO : test for more than 1 talent
    $talent = $fiche->find("article section.statistiques div.details div.talents")[0]->innerText();
    $start=23;
    $end=13;
    $talent=substr($talent,$start, strlen($talent)-$end-$start);
    switch ($talent){
        case "Lévitation": $final["Sol"]=0;break;
        case "Absorbe-Eau": $final["Eau"]=0;break;
        case "Torche": $final["Feu"]=0;break;
        case "Paratonnerre ":
        case "Absorbe-Volt":  $final["Electrik"]=0;break;
    }
    return $final;
}
