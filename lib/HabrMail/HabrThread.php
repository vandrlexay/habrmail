<?php

namespace HabrMail;

use Symfony\Component\DomCrawler\Crawler;

use HabrMail\HabrMessage;
use HabrMail\HtmlToText;

class HabrThread {

    protected $data;
    protected $crawler;
    protected $threadId = null;
    protected $commentTree = null;

    public function __construct(string $data) {
        $this->data = $data;
        $this->crawler = new Crawler($this->data);
    }

    /**
     * Make post from the article itself
     */
    public function getFirstPost() : HabrMessage {
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

        return new HabrMessage([
            'from' => $author,
            'subject' => $postTitle,
            'parent' => null,
            'created' => $this->parseDate($postDate),
            'id' => $postId,
            'body' => $postText,
            'thread' => $postId
        ]);
    }

    /**
     * Parse comments to array
     */
    public function getComments() : array {
        $comments = [];
        $this->crawler->filter('.comment')->each(
            function ($node, $i) use (&$comments) {

                $parent = $node->closest("li.content-list__item_comment")->filter(".parent_id");
                if ($parent->count() > 0) {
                    $parent = $parent->first()->attr("data-parent_id");
                    if ($parent === "0")
                        $parent = $this->threadId;
                }
                else {
                    $parent = $this->threadId;
                }
                
                $comments[] = new HabrMessage([
                    'from' => $node->filter(".user-info_inline")->first()->attr("data-user-login"),
                    'subject' => $node->filter(".comment__message")->first()->text(),
                    'parent' => $parent,
                    'created' => $this->parseDate($node->filter(".comment__date-time")->first()->text()),
                    'id' => $node->filter(".comment__head")->first()->attr("rel"),
                    'body' => $this->postToText($node->filter(".comment__message")->first()),
                    'thread' => $this->threadId
                ]);
            });

        return $comments;
    }


    /**
     * Return array of all posts in thread, including original article
     */
    public function getPosts() : array {
        $posts = [];
        $posts[] = $this->getFirstPost();
        $posts = array_merge($posts, $this->getComments());

        return $posts;
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
