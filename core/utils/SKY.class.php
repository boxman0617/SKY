<?php
class SKY
{
    public static $countries = array(
        'AFG','ALB','ALG','AND','ANG','ANT','ARG','ARM','ARU','ASA','AUS',
        'AUT','AZE','BAH','BAN','BAR','BDI','BEL','BEN','BER','BHU','BIH',
        'BIZ','BLR','BOL','BOT','BRA','BRN','BRU','BUL','BUR','CAF','CAM',
        'CAN','CAY','CGO','CHA','CHI','CHN','CIV','CMR','COD','COK','COL',
        'COM','CPV','CRC','CRO','CUB','CYP','CZE','DEN','DJI','DMA','DOM',
        'ECU','EGY','ERI','ESA','ESP','EST','ETH','FIJ','FIN','FRA','FSM',
        'GAB','GAM','GBR','GBS','GEO','GEQ','GER','GHA','GRE','GRN','GUA',
        'GUI','GUM','GUY','HAI','HKG','HON','HUN','INA','IND','IRI','IRL',
        'IRQ','ISL','ISR','ISV','ITA','IVB','JAM','JOR','JPN','KAZ','KEN',
        'KGZ','KIR','KOR','KSA','KUW','LAO','LAT','LBA','LBR','LCA','LES',
        'LIB','LIE','LTU','LUX','MAD','MAR','MAS','MAW','MDA','MDV','MEX',
        'MGL','MHL','MKD','MLI','MLT','MNE','MON','MOZ','MRI','MTN','MYA',
        'NAM','NCA','NED','NEP','NGR','NIG','NOR','NRU','NZL','OMA','PAK',
        'PAN','PAR','PER','PHI','PLE','PLW','PNG','POL','POR','PRK','PUR',
        'QAT','ROU','RSA','RUS','RWA','SAM','SEN','SEY','SIN','SKN','SLE',
        'SLO','SMR','SOL','SOM','SRB','SRI','STP','SUD','SUI','SUR','SVK',
        'SWE','SWZ','SYR','TAN','TGA','THA','TJK','TKM','TLS','TOG','TPE',
        'TRI','TUN','TUR','TUV','UAE','UGA','UKR','URU','USA','UZB','VAN',
        'VEN','VIE','VIN','YEM','ZAM','ZIM'
    );

    public static $us_states = array(
        'AL' =>'Alabama', 'AK'=>'Alaska', 'AZ'=>'Arizona',
        'AR' =>'Arkansas', 'CA'=>'California', 'CO'=>'Colorado',
        'CT' =>'Connecticut', 'DE'=>'Delaware', 'DC'=>'District Of Columbia',
        'FL' =>'Florida', 'GA'=>'Georgia', 'HI'=>'Hawaii',
        'ID' =>'Idaho', 'IL'=>'Illinois', 'IN'=>'Indiana',
        'IA' =>'Iowa', 'KS'=>'Kansas', 'KY'=>'Kentucky',
        'LA' =>'Louisiana', 'ME'=>'Maine', 'MD'=>'Maryland',
        'MA' =>'Massachusetts', 'MI'=>'Michigan', 'MN'=>'Minnesota',
        'MS' =>'Mississippi', 'MO'=>'Missouri', 'MT'=>'Montana',
        'NE' =>'Nebraska', 'NV'=>'Nevada', 'NH'=>'New Hampshire',
        'NJ' =>'New Jersey', 'NM'=>'New Mexico', 'NY'=>'New York',
        'NC' =>'North Carolina', 'ND'=>'North Dakota', 'OH'=>'Ohio',
        'OK' =>'Oklahoma', 'OR'=>'Oregon', 'PA'=>'Pennsylvania',
        'RI' =>'Rhode Island', 'SC'=>'South Carolina', 'SD'=>'South Dakota',
        'TN' =>'Tennessee', 'TX'=>'Texas', 'UT'=>'Utah',
        'VT' =>'Vermont', 'VA'=>'Virginia', 'WA'=>'Washington',
        'WV' =>'West Virginia', 'WI'=>'Wisconsin', 'WY'=>'Wyoming'
    );

    public static function GetCountries()
    {
        return array_change_key_case(array_combine(self::$countries, self::$countries));
    }

	public static function Version()
	{
		return trim(file_get_contents(SkyDefines::Call('SKYCORE').'/version.info'));
	}

    public static function RRMDIR($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach($files as $file)
            (is_dir("$dir/$file")) ? self::RRMDIR("$dir/$file") : unlink("$dir/$file");
        return rmdir($dir);
    }

	public static function RCP($src, $dst, $exclude = array())
	{
	    if(in_array(basename($src), $exclude))
	        return true;
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ($file = readdir($dir)))
		{
			if($file != '.'  && $file != '..'  && !in_array($file, $exclude))
			{
				if(is_dir($src . '/' . $file))
					self::RCP($src . '/' . $file, $dst . '/' . $file);
				else
					copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
		closedir($dir);
	}

	public static function IsCurl()
	{
		return function_exists('curl_version');
	}

	public static function DownloadFile($file_source, $file_target)
	{
		$rh = fopen($file_source, 'rb');
		$wh = fopen($file_target, 'w+b');
		if (!$rh || !$wh)
			return false;

		echo '[';
		while (!feof($rh))
		{
			if (fwrite($wh, fread($rh, 4096)) === FALSE)
				return false;
			echo '=';
			flush();
		}
		echo "]\n";

		fclose($rh);
		fclose($wh);

		return true;
	}

    public static function IsInApp()
    {
        return is_file(SkyDefines::Call('APPROOT').'/.skycore');
    }

	public static function LoadCore($ENV = 'DEV')
	{
		require_once(getenv('SKYCORE').'/configs/defines.php');
		SkyDefines::Define('APPROOT', getcwd());

        SkyDefines::Overwrite('ARTIFICIAL_LOAD', true);

        if(self::IsInApp())
            SkyL::Import(SkyDefines::Call('SKYCORE_CONFIGS').'/app_defines.php');

        SkyDefines::SetEnv($ENV);
	}

	//############################################################
	//# Title Methods
	//############################################################

    public static function to_title($title, $all_cap = array())
    {
        $not_cap = array('to', 'a', 'the', 'at', 'in', 'with', 'and', 'but', 'or');
        $words = explode(' ', strtolower($title));

        // First word always gets cap
        $words[0] = ucfirst($words[0]);
        for($i = 1; $i < count($words); $i++)
        {
            if(in_array($words[$i], $all_cap))
            {
                $words[$i] = strtoupper($words[$i]);
                continue;
            }
            if(!in_array($words[$i], $not_cap))
                $words[$i] = ucfirst($words[$i]);
        }
        return implode(' ', $words);

    }

	//############################################################
	//# Word plural/singular Methods
	//############################################################

    public static function singularize($word)
    {
        $singular = array (
            '/(quiz)zes$/i' => '$1',
            '/(address)es&/i' => '$1',
            '/(matr)ices$/i' => '$1ix',
            '/(vert|ind)ices$/i' => '$1ex',
            '/^(ox)en/i' => '$1',
            '/(alias|status)es$/i' => '$1',
            '/([octop|vir])i$/i' => '$1us',
            '/(cris|ax|test)es$/i' => '$1is',
            '/(shoe)s$/i' => '$1',
            '/(o)es$/i' => '$1',
            '/(bus)es$/i' => '$1',
            '/([m|l])ice$/i' => '$1ouse',
            '/(x|ch|ss|sh)es$/i' => '$1',
            '/(m)ovies$/i' => '$1ovie',
            '/(s)eries$/i' => '$1eries',
            '/([^aeiouy]|qu)ies$/i' => '$1y',
            '/([lr])ves$/i' => '$1f',
            '/(tive)s$/i' => '$1',
            '/(hive)s$/i' => '$1',
            '/([^f])ves$/i' => '$1fe',
            '/(^analy)ses$/i' => '$1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
            '/([ti])a$/i' => '$1um',
            '/(n)ews$/i' => '$1ews',
            '/s$/i' => ''
        );

        $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

        $irregular = array(
            'person' => 'people',
            'man' => 'men',
            'child' => 'children',
            'sex' => 'sexes',
            'move' => 'moves'
        );

        $ending_in_s = array(
            'status',
            'alias',
            'class',
            'rendezvous',
            'address'
        );

        $lowercased_word = strtolower($word);
        foreach ($uncountable as $_uncountable){
            if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
                //Log::corewrite('Uncountable singular [%s][%s]', 2, __CLASS__, __FUNCTION__, array($lowercased_word, $word));
                return $word;
            }
        }

        foreach ($irregular as $_plural=> $_singular){
            if (preg_match('/('.$_singular.')$/i', $word, $arr)) {
                //Log::corewrite('Irragular singular [%s][%s]', 2, __CLASS__, __FUNCTION__, array($word, $_singular));
                return preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $word);
            }
        }

        foreach ($singular as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                if(!in_array($word, $ending_in_s))
                {
                    //Log::corewrite('Singular singular [%s][%s]', 2, __CLASS__, __FUNCTION__, array($word, $rule));
                    return preg_replace($rule, $replacement, $word);
            }
        }
        }

        return false;
    }

	public static function pluralize($word)
	{
		$plural = array(
			'/(quiz)$/i' => '$1zes',
			'/^(ox)$/i' => '$1en',
			'/([m|l])ouse$/i' => '$1ice',
			'/(matr|vert|ind)ix|ex$/i' => '$1ices',
			'/(x|ch|ss|sh)$/i' => '$1es',
			'/([^aeiouy]|qu)ies$/i' => '$1y',
			'/([^aeiouy]|qu)y$/i' => '$1ies',
			'/(hive)$/i' => '$1s',
			'/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
			'/sis$/i' => 'ses',
			'/([ti])um$/i' => '$1a',
			'/(buffal|tomat)o$/i' => '$1oes',
			'/(bu)s$/i' => '$1ses',
			'/(alias|status)/i'=> '$1es',
			'/(octop|vir)us$/i'=> '$1i',
			'/(ax|test)is$/i'=> '$1es',
			'/s$/i'=> 's',
			'/$/'=> 's'
		);

		$uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

		$irregular = array(
		'person' => 'people',
		'man' => 'men',
		'child' => 'children',
		'sex' => 'sexes',
		'move' => 'moves');

		$lowercased_word = strtolower($word);

		foreach ($uncountable as $_uncountable){
			if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
				return $word;
			}
		}

		foreach ($irregular as $_plural=> $_singular){
			if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
				return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
			}
		}

		foreach ($plural as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}
		return false;
	}

	public static function UnderscoreToUpper($word)
	{
		$tmp = explode('_', $word);
		$return = "";
		foreach($tmp as $sub)
			$return .= ucfirst($sub);
		return $return;
	}

	public static function CleanFileArray()
    {
        $RETURN = array();
        foreach($_FILES as $main => $outter)
        {
            $RETURN[$main] = array();
            foreach($outter as $type => $tag)
            {
                foreach($tag as $tag_name => $info)
                {
                    if(!isset($RETURN[$main][$tag_name]))
                        $RETURN[$main][$tag_name] = array();
                    if(is_array($info))
                    {
                        foreach($info as $i => $value)
                        {
                            if(!isset($RETURN[$main][$tag_name][$i]))
                                $RETURN[$main][$tag_name][$i] = array();
                            $RETURN[$main][$tag_name][$i][$type] = $value;
                        }
                    } else {
                        $RETURN[$main][$tag_name][$type] = $info;
                    }
                }
            }
        }
        return $RETURN;
    }

    public static function CreditCardYears($years = 10)
    {
        $this_year = date('Y');
        $last_year = $this_year + $years;
        $options = '';
        for($i = $this_year; $i <= $last_year; $i++)
        {
            $options .= '<option value="'.$i.'">'.$i.'</option>';
        }
        return $options;
    }
}
