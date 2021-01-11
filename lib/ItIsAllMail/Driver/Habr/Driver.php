<?php

namespace ItIsAllMail\Driver\Habr;

use ItIsAllMail\DriverInterface;
use ItIsAllMail\HtmlToText;
use ItIsAllMail\Message;

use Symfony\Component\DomCrawler\Crawler;

class HabrDriver implements DriverInterface {

    protected $crawler;
    protected $driverCode = "habr.com";

    public function getCode() : string {
        return $this->driverCode;
    }

    public function matchURL(string $url) : bool {
        if (preg_match('/' . $this->getCode() . '/', $url))
            return true;
        else
            return false;
    }


    /**
     * Return array of all posts in thread, including original article
     */
    public function getPosts(array $source) : array {

        $html = file_get_contents($source["url"]);
        $this->crawler = new Crawler($html);
        
        $posts = [];
        $posts[] = $this->getFirstPost();
        $posts = array_merge($posts, $this->getComments());

        return $posts;
    }


    /**
     * Make post from the article itself
     */
    public function getFirstPost() : Message {
        $postContainer = $this->crawler->filter('div.post__wrapper');
        $author = $postContainer->filter(".user-info__nickname")->first()->text();
        $postText = $this->postToText(
            $postContainer->filter("#post-content-body")->first()
        );
        $postDate = $postContainer->filter(".post__time")->first()->text();
        $postTitle = $postContainer->filter(".post__title-text")->first()->text();
        $postId = $this->crawler->filter("meta")->reduce(function (Crawler $node, $i) {
            return $node->attr("property") === "aiturec:item_id";
        })->first()->attr("content");

        $this->threadId = $postId;

        return new Message([
            'from' => $author . "@" . $this->getCode(),
            'subject' => $postTitle,
            'parent' => null,
            'created' => $this->parseDate($postDate),
            'id' => $postId . "@" . $this->getCode(),
            'body' => $postText,
            'thread' => $postId . "@" . $this->getCode()
        ]);
    }

    /**
     * Parse comments to array
     */
    public function getComments() : array {
        $comments = [];
        $this->crawler->filter('.comment')->each(
            function ($node, $i) use (&$comments) {

                $isBanned = $node->filter(".comment__message_banned")->count() > 0;

                if ($isBanned) {
                    return;
                }

                $parent = $node->closest("li.content-list__item_comment")->filter(".parent_id");
                if ($parent->count() > 0) {
                    $parent = $parent->first()->attr("data-parent_id");
                    if ($parent === "0")
                        $parent = $this->threadId;
                }
                else {
                    $parent = $this->threadId;
                }
                
                $comments[] = new Message([
                    'from' => $node->filter(".user-info_inline")->first()->attr("data-user-login")  . "@" . $this->getCode(),
                    'subject' => $node->filter(".comment__message")->first()->text(),
                    'parent' => $parent . "@" . $this->getCode(),
                    'created' => $this->parseDate($node->filter(".comment__date-time")->first()->text()),
                    'id' => $node->filter(".comment__head")->first()->attr("rel")  . "@" . $this->getCode(),
                    'body' => $this->postToText($node->filter(".comment__message")->first()),
                    'thread' => $this->threadId  . "@" . $this->getCode()
                ]);
            });

        return $comments;
    }

    /**
     * Convert to text readable by CLI mail client
     */
    protected function postToText(Crawler $node) : string {
        return (new HtmlToText($node->html()))->getText();
    }

    /**
     * Parse habr.com date representation to DateTime
     */
    protected function parseDate(string $rawDate) : \DateTimeInterface {
        $months = [
            "января"   => "01",
            "февраля"  => "02",
            "марта"    => "03",
            "апреля"   => "04",
            "мая"      => "05",
            "июня"     => "06",
            "июля"     => "07",
            "августа"  => "08",
            "сентября" => "09",
            "октября"  => "10",
            "ноября"   => "11",
            "декабря"  => "12"
        ];
        
        $datePrepared = "";
        if (strpos($rawDate, "вчера") !== false) {
            $date = new \DateTime();
            $date->sub(new \DateInterval('P1D'));
            $datePrepared = preg_replace("/вчера/", $date->format("j m Y"), $rawDate);
        }
        elseif (strpos($rawDate, "сегодня") !== false) {
            $date = new \DateTime();
            $datePrepared = preg_replace("/сегодня/", $date->format("j m Y"), $rawDate);
        }
        else {
            $datePrepared = preg_replace_callback("/(" . implode("|", array_keys($months)) . ")/", function ($m) use ($months) {return $months[$m[1]];}, $rawDate);
        }
        $datePrepared = str_replace("в ", "", $datePrepared);

        $finalDate = \DateTime::createFromFormat("j m Y H:i", $datePrepared);
        
        return $finalDate;
    }


}
