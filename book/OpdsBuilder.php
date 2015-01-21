<?php

/**
 * @author Thomas Pellissier Tanon
 * @copyright 2011 Thomas Pellissier Tanon
 * @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
 */

/**
 * Allows to build OPDS feeds
 */
class OpdsBuilder {

        /**
         * @var string
         */
        private $exportBasePath;

        /**
         * @var BookProvider
         */
        private $bookProvider;

        /**
         * @var
         */
        private $lang;

        /**
         * @param BookProvider $bookProvider
         * @param string $lang
         * @param string $exportBasePath
         */
        public function __construct(BookProvider $bookProvider, $lang, $exportBasePath = '') {
                $this->bookProvider = $bookProvider;
                $this->lang = $lang;
                $this->exportBasePath = $exportBasePath;
        }

        public function buildFromCategory($categoryTitle) {
                $api = new Api($this->lang);
                $response = $api->completeQuery(array('generator' => 'categorymembers', 'gcmtitle' => $categoryTitle, 'gcmnamespace' => '0', 'prop' => 'info', 'gcmlimit' => '100'));
                if(!array_key_exists('query', $response))
                        throw new HttpException('Not Found', 404);

                $pages = $response['query']['pages'];

                $titles = array();
                foreach($pages as $page) {
                        $titles[] = $page['title'];
                }

                return $this->buildFromTitles($titles, $categoryTitle);
        }


        private function buildFromTitles(array $titles, $fromPage = '') {
                date_default_timezone_set('UTC');
                $generator = new AtomGenerator($this->exportBasePath);

                $dom = new DOMDocument("1.0", "UTF-8");
                $feed = $dom->createElement('feed');
                $generator->appendNamespaces($feed);
                $feed->setAttribute('xml:lang', $this->lang);
                $this->addNode($dom, $feed, 'title', $fromPage);
                $this->addNode($dom, $feed, 'updated', date(DATE_ATOM));
                if($fromPage !== '') {
                        $wsUrl = wikisourceUrl($this->lang, $fromPage);
                        $this->addNode($dom, $feed, 'id', $wsUrl, 'dcterms:URI');
                        $this->addLink($dom, $feed, 'alternate', $wsUrl, 'text/html');
                }

                foreach($titles as $title) {
                    $book = $this->bookProvider->get($title, true);
                    $entry = $generator->buildEntry($book, $dom);
                    $feed->appendChild($entry);
                }

                $dom->appendChild($feed);
                return $dom->saveXML();
        }

        private function addNode(DOMDocument $dom, DOMElement $head, $name, $value, $type = '') {
                if($value === '') {
                        return ;
                }

                $node = $dom->createElement($name, $value);

                if($type !== '') {
                        $node->setAttribute('xsi:type', $type);
                }

                $head->appendChild($node);
        }

        private function addLink(DOMDocument $dom, DOMElement $head, $rel, $href, $type = '') {
                $node = $dom->createElement('link');
                $node->setAttribute('rel', $rel);
                if($type !== '') {
                        $node->setAttribute('type', $type);
                }
                $node->setAttribute('href', $href);
                $head->appendChild($node);
        }
}
