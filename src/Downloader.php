<?php

/**
 * @author Randolph Roble <r.roble@arcanys.com>
 */
class Downloader
{

    /**
     * @var string
     */
    private $url;
    
    /**
     * @var string
     */
    private $output;
    
    /**
     * @var integer
     */
    private $timeout;
    
    /**
     * @var GuzzleHttp\Client
     */
    private $client;
    
    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct($url, $output = 'output', $timeout = 50)
    {
        $this->url = $url;
        $this->output = $output;
        $this->timeout = $timeout;
        $this->client = new GuzzleHttp\Client();
    }
    
    public function setLogger(Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
    
    private function log()
    {
        if ($this->logger) {
            $message = implode(' ', func_get_args());
            $this->logger->info($message);
        }
    }

    public function start($episode)
    {
        $this->log('Downloading episodes starting from', $episode);

        do {
            $result = $this->download($episode);
            if (!$result) {
                break;
            }
        } while ($episode++);

        $this->log('Done');
    }

    public function download($episode)
    {
        $this->log('Downloading episode', $episode);

        $page = 1;
        do {
            $result = $this->downloadPage($episode, $page);
            if (!$result) {
                break;
            }
        } while ($page++);

        $this->log('Done #', $episode);
        return $page > 1;
    }

    public function downloadPage($episode, $page)
    {
        $this->log('Page', $page);

        $url = sprintf($this->url, $episode, $page);
        try {
            $exists = $this->client->head($url);
            if ($exists->getStatusCode() !== 200) {
                return false;
            }
            if (!($img = $this->findImgUrl($url))) {
                $this->log(' .. not yet');
                return false;
            }
            $dest = $this->getDestinationFile($episode, $page);
            $success = $this->downloadImage($img, $dest);
//            $this->log(' .. OK');
            return $success;
        } catch (Exception $e) { // assume not found
            $this->log(' .. OK');
        }
        return false;
    }

    public function findImgUrl($url)
    {
        // look for this
        // <img id="img" width="800" height="1162" src="http://i997.mangareader.net/one-piece/778/one-piece-5518563.jpg" alt="One Piece 778 - Page 1" name="img" />
        $html = $this->client->get($url);
        $a = explode('id="img"', $html);
        if (!isset($a[1])) {
            return false;
        }
        $b = explode('src="', $a[1]);
        if (!isset($b[1])) {
            return false;
        }
        list($c, ) = explode('"', $b[1]);
        return $c;
    }

    public function getDestinationFile($episode, $page, $ext = '.jpg')
    {
        return sprintf('%s/%s/%s%s', 
                $this->output, 
                sprintf('%03d', $episode), 
                sprintf('%02d', $page), 
                $ext);
    }

    public function downloadImage($url, $dest)
    {
        if (!file_exists($dir = dirname($dest))) {
            mkdir($dir, 0777, true);
        }
        $result = $this->client->get($url, [
            'save_to' => $dest, 
            'timeout' => $this->timeout,
        ]);
        return $result->getStatusCode() == 200;
    }

}
