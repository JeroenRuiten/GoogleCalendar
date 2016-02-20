<?php

$apiUrl = "http://api.openhack.nl";
$jsonData = file_get_contents($apiUrl);
$data = json_decode($jsonData);
foreach ($data->items as $event) {
    if ($event->start->dateTime) $event->start->date = date('Y-m-d', strtotime($event->start->dateTime));
    if ($event->end->dateTime) $event->end->date = date('Y-m-d', strtotime($event->end->dateTime));
}

function CacheStaticMaps($place) {
    $filename = hash("sha512", $place);
    $dir = "cache/staticmaps";
    $location = "{$dir}/IMG-{$filename}.png";
    
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if(!file_exists($location)){
        $mapsStaticLoc = str_replace('{place}', $place, 'http://maps.googleapis.com/maps/api/staticmap?center={place}&zoom=15&scale=2&size=1000x350&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid%7Ccolor:0xff0000%7Clabel:1%7C{place}');
        $file = file_get_contents($mapsStaticLoc);
        file_put_contents($location, $file);
    }
    return $location;
}

function GetMapsWidget($place) {
    if(empty($place)){
        return "";
    }
    
    $mapsPlace = str_replace(' ', '+', $place);
    $mapsStaticLoc = CacheStaticMaps($mapsPlace);
    $mapsLoc = str_replace('{place}', $mapsPlace, 'https://www.google.nl/maps/place/{place}');
    //$template = "
    //    <a target=\"_blank\" href=\"{$mapsLoc}\">
    //        <figure>
    //            <img src=\"{$mapsStaticLoc}\">
    //            <figcaption>{$place}</figcaption>
    //        </figure>
    //    </a>
    //";
    $template = "
        <header style=\"background-image: url({$mapsStaticLoc})\">
            <div class=\"place\"><a target=\"_blank\" href=\"{$mapsLoc}\">{$place}</a></div>
        </header>
    ";
    return $template;
}

function FormatDatum($event) {
    $dag = ([
        '1' => 'maandag',
        '2' => 'dinsdag',
        '3' => 'woensdag',
        '4' => 'donderdag',
        '5' => 'vrijdag',
        '6' => 'zaterdag',
        '7' => 'zondag'
    ]);
    $startDag = $dag[date('N', strtotime($event->start->date))];
    $maand = ([
        '01' => 'januari',
        '02' => 'februari',
        '03' => 'maart',
        '04' => 'april',
        '05' => 'mei',
        '06' => 'juni',
        '07' => 'juli',
        '08' => 'augustus',
        '09' => 'september',
        '10' => 'oktober',
        '11' => 'november',
        '12' => 'december'
    ]);
    $startMaand = $maand[date('m', strtotime($event->start->date))];
    $startDatum = (int) date('d', strtotime($event->start->date));
    if ($event->start->date !== $event->end->date) {
        $endDag = $dag[date('N', strtotime($event->end->date))];
        $endMaand = $maand[date('m', strtotime($event->end->date))];
        $endDatum = (int) date('d', strtotime($event->end->date));
        //return "<p>Van {$startDag} {$startDatum} {$startMaand} tot {$endDag} {$endDatum} {$endMaand}</p>";
        return "Van {$startDag} {$startDatum} {$startMaand} tot {$endDag} {$endDatum} {$endMaand}";
    } else {
        //return "<p>{$startDag} {$startDatum} {$startMaand}</p>";
        return "{$startDag} {$startDatum} {$startMaand}";
    }
}

function FormatEvent($event) {
    //$template = "
    //    <div class=\"event\">
    //        <div>
    //            <h3>{$event->summary}</h3>
    //            <p>" . FormatDatum($event) . "</p>
    //            <p>" . URL2Link(RemoveURL($event->description)) . "</p>
    //            " . GetMapsWidget($event->location) . "
    //        </div>
    //        ". (!empty(GetURL($event->description)) ? "<a class=\"cta\" href=\"".GetURL($event->description)."\">More info</a>" : "") . "
    //    </div>
    //";
    $template = "
        <section class=\"event\">
            " . GetMapsWidget($event->location) . "
            </header>
            <main>
                <p>" . (!empty(GetURL($event->description)) ? "<a class=\"cta\" href=\"" . GetURL($event->description) . "\">More info</a>" : "") . "</p>
                <h2>" . FormatDatum($event) . "</h2>
                <h1>{$event->summary}</h1>
                <p>" . URL2Link(RemoveURL($event->description)) . "</p>
            </main>
        </section>
    ";
    return $template;
}

//Vervang url door link
function URL2Link($tekst)
{
    // The Regular Expression filter
    $protocol = '(http|https|ftp|ftps)\:\/\/';
    $mid = '([a-zA-Z0-9\-]+\.)+[a-zA-Z]+(\/\S*)?';
    $reg_exUrl = "/{$protocol}{$mid}/";
    
    $tekst = preg_replace($reg_exUrl, '<a href="${0}">${0}</a>', $tekst);
    return $tekst;
    
}//URL2Link

//Haal een URL uit de tekst
function GetURL($tekst)
{
    // The Regular Expression filter
    $reg_exUrl = "/\((url\:)(http|https|ftp|ftps)\:\/\/([a-zA-Z0-9\-\.]+\.)[a-zA-Z]+(\/\S*)?\)/";
    
    // Check if there is a url in the text
    if(preg_match($reg_exUrl, $tekst, $url)) {
    
           // only return the url
           
           // remove "(url:" and ")" from the match
           $url = str_replace('(url:', '', $url[0]);
           $url = rtrim($url, ')');
           return $url;
    
    } else {
    
           // if no urls in the text just return an empty string
           return '';
    
    }
}//GetURL

//Verwijder een URL uit de tekst
function RemoveURL($tekst)
{
    $url = '(url:' . GetURL($tekst) . ')';
    $tekst = str_replace($url, '', $tekst);
    return trim($tekst);
}//RemoveURL
?>
<!DOCTYPE html>
<html>
    <head>
        <meta name='viewport' content='width=320,initial-scale=1,user-scalable=0'>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <meta name="author" content="Johan Groenen (c) 2015">
        
        <link rel='image_src' href='http://www.openhack.nl/openhack_binary_color_2.png'/>
        <link rel='shortcut icon' type='image/png' href='http://www.openhack.nl/openhack_binary_color_2.png'>
        
        <title>Open Hack - Hackathons in Nederland</title>
        <link href='//fonts.googleapis.com/css?family=Rokkitt' rel='stylesheet' type='text/css'>
        <link href='//fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
        <link href="./css/reset.css" rel="stylesheet"/>
        <link href="./css/style.css" rel="stylesheet"/>
    </head>
    <body>
        <div class="container">
            <header>
                <img src="openhack_binary_color_2.png">
                <div>
                    <h1>Open Hack</h1>
                    <h2>Hackathons in the Netherlands</h2>
                </div>
            </header>
            
            <h2>Binnenkort</h2>
            <ul>
                <?php foreach (array_reverse($data->items) as $event) { ?>
                    <?php if (date('Y-m-d', strtotime($event->end->date)) >= date('Y-m-d')) { ?>
                        <li><?= FormatEvent($event) ?></li>
                    <?php } ?>
                <?php } ?>
            </ul>
    
            <h2>Eerder</h2>
            <ul>
                <?php foreach (array_reverse($data->items) as $event) { ?>
                    <?php if (date('Y-m-d', strtotime($event->end->date)) < date('Y-m-d')) { ?>
                        <li><?= FormatEvent($event) ?></li>
                    <?php } ?>
                <?php } ?>
            </ul>
            
            <?php //<iframe src="https://www.google.com/calendar/embed?showTitle=0&amp;showNav=0&amp;showPrint=0&amp;showTabs=0&amp;showCalendars=0&amp;height=600&amp;wkst=1&amp;bgcolor=%23ffffff&amp;src=nvj180m0tgimudci661idpsb80%40group.calendar.google.com&amp;color=%23691426&amp;ctz=Europe%2FAmsterdam" style=" border-width:0 " width="800" height="600" frameborder="0" scrolling="no"></iframe>
            ?>
        </div>
    
    </body>
</html>