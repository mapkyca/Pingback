<?php

namespace IdnoPlugins\Pingback {

    class Endpoint extends \Idno\Common\Page
    {

        private function success($message) {
            $data = "<?xml version=\"1.0\"?>\n";
            $data .= "<methodResponse>
    <params>
        <param>
        <value><string>$message</string></value>
        </param>
    </params>
</methodResponse>";
            
            header("Content-Type: text/xml");
            header("Content-Length: " . strlen($data));
            
            echo $data;
        }
        
        private function error($message, $code = 0) {
            $data = "<?xml version=\"1.0\"?>\n";
            $data .= "<methodResponse>
    <fault>
        <value>
            <struct>
                <member>
                    <name>faultCode</name>
                    <value><int>$code</int></value>
                </member>
                
                <member>
                    <name>faultString</name>
                    <value><string>$message</string></value>
                </member>
            </struct>
        </value>
    </fault>
</methodResponse>";
            
            header("Content-Type: text/xml");
            header("Content-Length: " . strlen($data));
            
            echo $data;
        }
        
        
        function getContent() {
            $this->error('No target specified');
        }

        function post() {

            $post = trim(file_get_contents("php://input"));
            
            try {
            
                if (!empty($post))
                {
                    if ($xml = XmlParser::unserialise($post)) {

			// Get source and target url
                        $source = $xml->children[1]->children[0]->children[0]->children[0]->content;
                        $target = $xml->children[1]->children[1]->children[0]->children[0]->content;

			\Idno\Core\site()->logging->log("Pingback: Pingback recieved, source = $source, target = $target", LOGLEVEL_DEBUG);
			
                        // Do we have a source and target URL?
                        if (!empty($source) && !empty($target)) {
                            // Get the page handler for target
                            if ($page = \Idno\Core\site()->getPageHandler($target)) {
                                if ($source_content = \Idno\Core\Webservice::get($source)) {

                                    if (substr_count($source_content['content'],$target) || $source_content['response'] == 410) {
                                        $source_mf2 = \Idno\Core\Webmention::parseContent($source_content['content']);

                                        // Set source and target information as input variables
                                        $page->setPermalink();
                                        if ($page->webmentionContent($source, $target, $source_content, $source_mf2)) {
                                            $this->setResponse(202);    // Pingback ok
                                            $this->success('OK');
                                            exit;
                                        } else 
                                            throw new \Exception('This is not pingable.', 49);

                                    } else {
					\Idno\Core\site()->logging->log("Pingback: No link from $source to $target", LOGLEVEL_DEBUG);
                                        throw new \Exception('The source URI does not contain a link to the target URI.', 17);
                                    }

                                }
                                else
                                    throw new \Exception('The source content could not be obtained.', 16);
                            }
                            else
                                throw new \Exception('The target page does not exist.', 32);
                        }
                        else
                            throw new \Exception('Source and target variables missing.');
                    }
                    else
                        throw new \Exception('Problem parsing XML Pingback.');
                }
                else 
                    throw new \Exception('No POST data');

            } catch (\Exception $e) {
		\Idno\Core\site()->logging->log("Pingback: " . $e->getMessage(), LOGLEVEL_ERROR);
                $this->error($e->getMessage());
                exit;
            }
        }
    }

}