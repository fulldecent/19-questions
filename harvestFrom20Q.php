<?php
header('Content-type: text/plain');

################################################################################
## This is your scraping framework
################################################################################
class Scraper {
  var $referrer;
  var $page;

  function loadUrl($url, $post = '')
  {
    if (preg_match('|^/|', $url)) {
      $url = preg_replace('|(://[^/]+)/.*$|', '$1', $this->referrer) . $url;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $this->referrer);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    if (!empty($post)) {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    $this->referrer = $url;
    $this->page = curl_exec($ch);
    echo "##\n## LOADED URL {$url}\n##\n{$this->page}\n\n";
  }

  function preg_extract_first($pattern)
  {
    if (!preg_match($pattern, $this->page, $matches)) return null;
    if (count($matches) < 2) return null;
    echo "##\n## EXTRACTED: {$pattern}\n## GOT: {$matches[1]}\n##\n\n";
    return $matches[1];
  }
}

################################################################################
## Now get to work
################################################################################
$scraper = new Scraper();
$scraper->referrer = 'http://y.20q.net/play';

// Load login page
$scraper->loadUrl('http://y.20q.net/gsq-en');
$actionUrl = $scraper->preg_extract_first('/<form method=post action="([^"]+)">/');

// Post form to start game
$scraper->loadUrl($actionUrl, ['submit' => '  Play  ']);

$i = 1;
do {
  $scraper->page = preg_replace('|<hr.*|', '', $scraper->page);
  $buttons = [];
  $buttons[] = $scraper->preg_extract_first('|<a href="([^"]+)" [^>]*>Unknown</a></nobr>|');
  $buttons[] = $scraper->preg_extract_first('|<a href="([^"]+)" [^>]*>&nbsp;Yes&nbsp;</a>|');
  $buttons[] = $scraper->preg_extract_first('|<a href="([^"]+)" [^>]*>&nbsp;&nbsp;No&nbsp;&nbsp;</a>|');
  $buttons[] = $scraper->preg_extract_first('|<a href="([^"]+)"[^>]*>Right</a>|');
  $buttons = array_filter($buttons);
  if (!count($buttons)) break;
  $url = $buttons[array_rand($buttons)];
  $scraper->loadUrl($url);
  $i++;
} while($i < 25);

#TODO: Output the stuff we learned, like:
#
# <big><b>You were thinking of gunpowder.</b></big><br>
# You said it's classified as Unknown, 20Q was taught by other players that the answer is Mineral.<br>
# Is it shiny? You said Yes, 20Q was taught by other players that the answer is No.<br>
# Do you use it when it rains? You said No, 20Q was taught by other players that the answer is Yes.<br>
#
