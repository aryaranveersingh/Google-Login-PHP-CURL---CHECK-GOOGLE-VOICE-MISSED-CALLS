<?php

class GoogleVoice {
	// Google account credentials.
	private $_login;
	private $_pass;

	// Special string that Google Voice requires in our POST requests.
	private $country_code;

	// File handle for PHP-Curl.
	private $_ch;

	// The location of our cookies.
	private $_cookieFile;

	public function __construct($login, $pass, $cc) {
		$this->_login = $login;
		$this->_pass = $pass;
		$this->country_code = $cc;
		$this->_cookieFile = 'gvCookies.txt';
		// Extra file to save info on cookies generation date
		$this->_cookieDateFile = 'do-not-remove-required-file.txt';
		$this->_logIn();
	}



	private function _logIn() {
		global $conf;


		$DATE = @file_get_contents($this->_cookieDateFile);

		
		echo("\nToday's Date: $DATE\n");
		if($DATE < date('Y-m-d',strtotime('now'))){
			unlink('gvCookies.txt');
			echo("\nDay Change Resetting cookies\n");
		}
		else{
			echo("\nValid cookies fetching data, no login required\n");
			return true;
		}

		
		// GLOBAL SETUP - setup curl host for fething and posting the data to google
		$this->_ch = curl_init();
		curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $this->_cookieFile);
		curl_setopt($this->_ch, CURLOPT_COOKIEFILE,$this->_cookieFile);
		curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36");
		

		// Step 1: Navigate to google and get the required session state var and azt param required to login
		$URL='https://accounts.google.com/ServiceLogin?service=grandcentral&passive=1209600&continue=https://voice.google.com/signup&followup=https://voice.google.com/signup';
		// // &Email='.$this->_login;  //adding login to GET prefills with username
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_cookieFile);
		curl_setopt($ch, CURLOPT_COOKIEFILE,$this->_cookieFile);
	    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36");
	    $html = curl_exec($ch);
	    
		preg_match('/WIZ_global_data \= (.+)<\/script><meta charset="utf-8"\/>/', $html,$match);

		$obj  = json_decode(str_replace("WIZ_global_data = ","",str_replace(';</script><meta charset="utf-8"/>', "", $match[0])));
		$txmp = str_replace('"]',"",preg_replace('/\%\.\@\."xsrf",null,\[""\],"/',"",preg_replace('/\s+/','',$obj->OewCAd)));

		preg_match('/data-initial-setup-data="(.+)&quot;,\[&quot;/', $html,$match);
		$sessionstate  = str_replace("%.@.null,null,null,[false],null,null,null,null,null,&quot;$this->country_code&quot;,null,null,null,&quot;","",$match[1]);

		// Enable put content if you wish to test out the output
		// file_put_contents('sample.html', $html);
		
		curl_setopt($this->_ch, CURLOPT_URL, $URL);
		$html = curl_exec($this->_ch);

		// Now setup the post parameters to send the login request
		$postarray = $this->dom_get_input_tags($html);
		$postarray['cookiesDisabled'] = 'false';
		$postarray['gmscoreversion']= 'undefined';
		$postarray['checkConnection']=' ';
		$postarray['checkedDomains']= 'youtube';
		$postarray['pstMsg']= '1';
		$postarray['continue']= 'https://voice.google.com/u/0/calls';
		// Setup your Device info array to enable google understand your location
		$postarray['deviceinfo'] = json_encode([null,null,null,[],null,"$this->country_code",null,null,[],"GlifWebSignIn",null,[null,null,[],null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,[],null,null,null,[],[]],null,null,null,null,null,null,false]);
		// Google BotGuard param to help it identify request type is to identify account
		$postarray['bgRequest'] = json_encode(["identifier","!JiWlJQRC8NCYyz23mjNYaAJDNV53-dACAAAAaFIAAAARmQNdytLPusxfaoHo-yfAjeKZoltyUlCQmfIUWvz_iWWpRs2hit94iG53b7QE3rdvQ2k9-VsnmaL38O_KQKkslT--B36akl7mW6fU8J5kOACih-CYFo1ncdR972nBMzTMxqKDmwR2uduLk3P1bSYG0l0_sn6zPAdVp2oZtMSQH6qxf6NVQxploqQ1ik5JgyMnYv9t54Tw7jC4RgT__ldsFMYjmJKCCw6OC5wMiXlCppoe7Qac5rOfq3OxXqA6K7ZSS6d9rHJmnJJTmdM_JkLoyyCs3GQBJKDEfzIedKN7tYqUbF0Szvpuinpd7ckzgAHJ0gVQYZlBLra_yOYYMTBeJEHJ45ZM6urBAKPHTwi20Q0Wufw3fagMWLfVcP_Jqkb8RBlfl9O23K4r7HLNRzGQsyPYzHs0Ep5Iz9Ma5EZA_OmAInByllN0JFKX2p1_4_ZQnGuCDMO8eC3Z3-aWW-TlmQUpCTIPeve5gQ7fRmkXcU7NOx2rkP4UALAuXA2FOpFE9N9msRRBa0RPFAvnOYLkKjlw0JqcNC6vnqL7n2_MBZpF-6uBSq8qrCKJEuhh66TsS9PN17JcT5SB13tLF_7ElB_TZNHJV_2q1AFG6OYQbmlI7ud-9h92wm-16OehXEXwlbhLe3K1EkBruazfHm9svidKVLT6VPhTqk35dXByHaW_Tf5gu551GVjkKSbTMTRKazAtgCIjhLxHwy-wM4vgyejdYQrRQ2EwWEKrI35y_gZ6e7OxQHJZuDqG-g-eJKWf4KWDZ0eGExNUR1AkWY9fH3rFqlHwos18eGlGxTPgDVbiBPdWuSJU4dwoLjcOmlWHjQJHPxFdQlJIkQoI4rHKLQGsKuIYwbSrk9d54ryjjdKVxXzGep2jTHsrnC_PuMtKxcxWnQOA_w8TEIJW1_RGnfKijn_UfxR2EC1C3cG03rbCegSTN7h4N5bSCWiXGUg6cuovm-3rYn0Saxt1bMOrTnqadzPOlKRF4KHQq20VnNjTzgi6H798t4gLF8neU4zA888GhLlQ-KnQIrruFJat9aBgXbr4dd-D9MR9st2u89XajaV7ONpaP5ZkM7p_G-HxEf1sFgcxgUzG-VHgmT1_j3ACeuiLpaPm7_G7ujG50eff0jrQZRur-YraNLRNVBE-"]);
		$postarray['flowName'] =  'GlifWebSignIn';
		$postarray['hiddenPassword'] = $this->_pass;//Add password to POST array
		$postarray['identifier'] = $this->_login;  //Add password to POST array
		$postarray['flowEntry'] =  'ServiceLogin';
		$postarray['f.req'] = json_encode([$this->_login,$sessionstate,[],null,"$this->country_code",null,null,2,false,true,[null,null,[2,1,null,1,"https://accounts.google.com/ServiceLogin?service=grandcentral&passive=1209600&continue=https://www.google.com/voice/b/0/redirection/voice&followup=https://www.google.com/voice/b/0/redirection/voice#inbox",null,[],4,[],"GlifWebSignIn",null,[]],1,[null,null,[],null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,null,[],null,null,null,[],[]],null,null,null,true],$this->_login,null,null,null,true,true,[]]);
		$postarray['azt'] = $txmp ;
		$postarray['continue']= 'https://voice.google.com/u/0/calls';
		$reqid = strtotime('now') % 10000;
		$URL='https://accounts.google.com/_/lookup/accountlookup?hl=en&_reqid='.$reqid.'&rt=j';  // 
		
		// Send HTTP POST service login request using captured input information.
		
		curl_setopt($this->_ch, CURLOPT_URL, $URL);
		curl_setopt($this->_ch, CURLOPT_POST, TRUE);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36");
		curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array(
        'referer: https://accounts.google.com/ServiceLogin?service=grandcentral&passive=1209600&continue=https://www.google.com/voice/b/0/redirection/voice&followup=https://www.google.com/voice/b/0/redirection/voice#inbox',
        'google-accounts-xsrf: 1',
        'origin: https://accounts.google.com',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-origin',
        'x-same-domain: 1',
        'content-type: application/x-www-form-urlencoded;charset=UTF-8',
        'content-length: '.strlen(http_build_query($postarray))
        ));
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($postarray));
		$html = curl_exec($this->_ch);
		
		file_put_contents('sample.html', $html);
		 // Using DOM keeps the order of the name/value from breaking the code.

		preg_match('/\[\[\["gf\.alr",1,"(.+)",\[\[/', $html,$match);
		$sessionstate  = ($match[1]);
		$postarray['f.req'] = json_encode([$sessionstate,null,1,null,[1,null,null,null,["1hcmmYVt",null,true]]]);

		preg_match('/\["gf.ttu",0,"(.+)"\]/', $html,$match);
		$challangeurlparam  = ($match[1]);
		// $reqid = strtotime(date('H:i:s',strtotime('now'))) % 100000;
		
		curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array(
        'google-accounts-xsrf: 1',
        'origin: https://accounts.google.com',
        // 'sec-fetch-dest: empty',
        // 'sec-fetch-mode: cors',
        // 'sec-fetch-site: same-origin',
        'x-same-domain: 1',
        'content-type: application/x-www-form-urlencoded;charset=UTF-8',
        'content-length: '.strlen(http_build_query($postarray)),
        'referer: https://accounts.google.com/signin/v2/challenge/pwd?service=grandcentral&passive=1209600&continue=https%3A%2F%2Fvoice.google.com%2Fsignup&followup=https%3A%2F%2Fvoice.google.com%2Fsignup&flowName=GlifWebSignIn&flowEntry=ServiceLogin&cid=1&navigationDirection=forward&TL='.$challangeurlparam
        ));
		$reqid = strtotime('now') % 10000;
		$URL = "https://accounts.google.com/_/signin/challenge?hl=en&TL=".$challangeurlparam."&_reqid=$reqid&rt=j";
		curl_setopt($this->_ch, CURLOPT_URL, $URL);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($postarray));
		curl_setopt($this->_ch, CURLOPT_HEADER, 1);
		$html = curl_exec($this->_ch);  
		
		// multi-cookie variant contributed by @Combuster in comments
		preg_match_all('/^set-cookie:\s*([^;]*)/mi', $html, $matches);
		$cookies = array();
		
		foreach($matches[1] as $item) {
		    parse_str($item, $cookie);
		    $cookies = array_merge($cookies, $cookie);
		}
		$fp = fopen($this->_cookieFile ,'w+');
		fwrite($fp,serialize($cookies));
		fclose($fp);
		$fp = fopen($this->_cookieDateFile ,'w+');
		fwrite($fp,date('Y-m-d',strtotime('now')));
		fclose($fp);

		if(empty($html)){
			echo "Successfully Login";
			var_dump($html);
		}
		
	}

	public function dom_dump($obj) {
		if ($classname = get_class($obj)) {
			$retval = "Instance of $classname, node list: \n";
			switch (TRUE) {
				case ($obj instanceof DOMDocument):
					$retval .= "XPath: {$obj->getNodePath()}\n".$obj->saveXML($obj);
					break;
				case ($obj instanceof DOMElement):
					$retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
					break;
				case ($obj instanceof DOMAttr):
					$retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
					break;
				case ($obj instanceof DOMNodeList):
					for ($i = 0; $i < $obj->length; $i++) {
						$retval .= "Item #$i, XPath: {$obj->item($i)->getNodePath()}\n"."{$obj->item($i)->ownerDocument->saveXML($obj->item($i))}\n";
					}
					break;
				default:
					return "Instance of unknown class";
			}
		}
		else {
			return 'no elements...';
		}
		return htmlspecialchars($retval);
	}

	/**
	 * Source from http://www.binarytides.com/php-get-name-and-value-of-all-input-tags-on-a-page-with-domdocument/
	 * Generic function to fetch all input tags (name and value) on a page
	 * Useful when writing automatic login bots/scrapers
	 */
	private function dom_get_input_tags($html)
	{
	    $post_data = array();

	    // a new dom object
	    $dom = new DomDocument;

	    //load the html into the object
	    @$dom->loadHTML($html);  //@suppresses warnings
	    //discard white space
	    $dom->preserveWhiteSpace = FALSE;

	    //all input tags as a list
	    $input_tags = $dom->getElementsByTagName('input');

	    //get all rows from the table
	    for ($i = 0; $i < $input_tags->length; $i++)
	    {
	        if( is_object($input_tags->item($i)) )
	        {
	            $name = $value = '';
	            $name_o = $input_tags->item($i)->attributes->getNamedItem('name');
	            if(is_object($name_o))
	            {
	                $name = $name_o->value;

	                $value_o = $input_tags->item($i)->attributes->getNamedItem('value');
	                if(is_object($value_o))
	                {
	                    $value = $input_tags->item($i)->attributes->getNamedItem('value')->value;
	                }

	                $post_data[$name] = $value;
	            }
	        }
	    }

	    return $post_data;
	}

}

?>
