<?php


class sfPhastUtils{

    public static function getpartial($templateName, $vars = array())
    {
        $context = sfContext::getInstance();

        if (false !== $sep = strpos($templateName, '/'))
        {
            $moduleName   = substr($templateName, 0, $sep);
            $templateName = substr($templateName, $sep + 1);
        }
        else
        {
            $moduleName = $context->getActionStack()->getLastEntry()->getModuleName();
        }
        $actionName = '_'.$templateName;

        $class = sfConfig::get('mod_global_partial_view_class', 'sf').'PartialView';
        $view = new $class($context, $moduleName, $actionName, '');
        $view->setPartialVars(true === sfConfig::get('sf_escaping_strategy') ? sfOutputEscaper::unescape($vars) : $vars);

        return $view->render();
    }

    public static function geturl($page, $absolute = false){
        return ($absolute ? '' . sfContext::getInstance()->getRequest()->getUriPrefix() : '') . sfConfig::get("app_url_$page", $page);
    }

    public static  function getlines($string){
        return preg_split('/[\n\r]+/u', $string);
    }

    public static function break_words($string, $break = '\n'){
        return preg_replace('/\s+/u', $break, trim($string));
    }

    public static function encrypt($content){
        $td = mcrypt_module_open('des', '', 'ecb', '');
        $key = substr(sfConfig::get('app_encrypt_key'), 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $output = mcrypt_generic($td, $content);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $output;
    }

    public static function decrypt($content){
        if(!$content) return '';
        $td = mcrypt_module_open('des', '', 'ecb', '');
        $key = substr(sfConfig::get('app_encrypt_key'), 0, mcrypt_enc_get_key_size($td));
        $iv_size = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $output = mdecrypt_generic($td, $content);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return preg_replace('/\\0+$/', '', $output);
    }


    public static function assignRels($values, $object, $rel_class, $source_method, $rel_method){

        $values_ex = [];
        foreach($object->{'get' . $rel_class . 's'}() as $rel){
            if(in_array($rel->{'get' . $rel_method}(), $values)){
                $values_ex[] = $rel->{'get' . $rel_method}();
            }else{
                $rel->delete();
            }
        }
        foreach(array_diff($values, $values_ex) as $id){
            $rel = new $rel_class();
            $rel->{'set' . $source_method}($object->getId());
            $rel->{'set' . $rel_method}($id);
            $rel->save();
        }

        return $object;

    }

    public static function strtotime($str){
        if($result = strtotime($str)) return $result;
        $months = array('..','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря');
        preg_match("/(\d{1,2})\040*(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\040*(\d{2,4})/i", $str, $matches);
        if($matches) return strtotime($matches[3].'-'.array_search($matches[2], $months).'-'.$matches[1]);
        return;
    }

    public static function date($format = null, $timestamp = null){
        if(is_int($format)){
            $timestamp = $format;
            $format = null;
        }
        if(!$timestamp) $timestamp = time();
        if(!$format) $format = 'simple';
        $months = array('..','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря');
        $weeks = array('воскресенье','понедельник','вторник','среда','четверг','пятница','суббота');
        $date = getdate($timestamp);
        if($format == 'full'){
            return "{$date['mday']} {$months[$date['mon']]} {$date['year']} ({$weeks[$date['wday']]})";
        }else if($format == 'simple'){
            return "{$date['mday']} {$months[$date['mon']]} {$date['year']}";
        }else if($format == 'simpleY'){
            return "{$date['mday']} {$months[$date['mon']]} <span class='year'>{$date['year']}</span>";
        }else if($format == 'month_date'){
            return $months[$date['mon']];
        }else if($format == 'short'){
            return "{$date['mday']} {$months[$date['mon']]}";
        }else if($format == 'month_day'){
            return date('d', $timestamp)."<span> {$months[$date['mon']]}</span>";
        }else if($format == 'month'){
            $months = array('..','Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь');
            return "{$months[$date['mon']]}";
        }else{
            return date($format,$timestamp);
        }
    }

    public static function transliterate($string){

        $string = strtr(mb_strtolower($string), [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => 'y', 'ы' => 'yi', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            ' ' =>  '-', '.' =>  '-', '/' =>  '-'
        ]); 
        
        return preg_replace(['/([_-])+/', '/(^[\s_-]*|[\s_-]*$|[^\w_-])/i'], ['\\1', ''], $string);
    }



	public static function parseBoolean($value){
		return in_array(strtolower($value), array('true', 'on', '+', 'yes', 'y'));
	}

	/**
	 * Спизжено с sfYamlInline
	 */
	public static function evaluateScalar($scalar)
	{
		$scalar = trim($scalar);
		$trueValues = array('true', 'on', '+', 'yes', 'y');
		$falseValues = array('false', 'off', '-', 'no', 'n');

		switch (true)
		{
			case 'null' == strtolower($scalar):
			case '' == $scalar:
			case '~' == $scalar:
				return null;
			case 0 === strpos($scalar, '!str'):
				return (string) substr($scalar, 5);
			case 0 === strpos($scalar, '! '):
				return intval(self::parseScalar(substr($scalar, 2)));
			case 0 === strpos($scalar, '!!php/object:'):
				return unserialize(substr($scalar, 13));
			case ctype_digit($scalar):
				$raw = $scalar;
				$cast = intval($scalar);
				return '0' == $scalar[0] ? octdec($scalar) : (((string) $raw == (string) $cast) ? $cast : $raw);
			case in_array(strtolower($scalar), $trueValues):
				return true;
			case in_array(strtolower($scalar), $falseValues):
				return false;
			case is_numeric($scalar):
				return '0x' == $scalar[0].$scalar[1] ? hexdec($scalar) : floatval($scalar);
			case 0 == strcasecmp($scalar, '.inf'):
			case 0 == strcasecmp($scalar, '.NaN'):
				return -log(0);
			case 0 == strcasecmp($scalar, '-.inf'):
				return log(0);
			case preg_match('/^(-|\+)?[0-9,]+(\.[0-9]+)?$/', $scalar):
				return floatval(str_replace(',', '', $scalar));
			case preg_match(self::getTimestampRegex(), $scalar):
				return strtotime($scalar);
			default:
				return (string) $scalar;
		}
	}

	/**
	 * Спизжено с sfYamlInline
	 */
	static protected function getTimestampRegex()
	{
		return <<<EOF
    ~^
    (?P<year>[0-9][0-9][0-9][0-9])
    -(?P<month>[0-9][0-9]?)
    -(?P<day>[0-9][0-9]?)
    (?:(?:[Tt]|[ \t]+)
    (?P<hour>[0-9][0-9]?)
    :(?P<minute>[0-9][0-9])
    :(?P<second>[0-9][0-9])
    (?:\.(?P<fraction>[0-9]*))?
    (?:[ \t]*(?P<tz>Z|(?P<tz_sign>[-+])(?P<tz_hour>[0-9][0-9]?)
    (?::(?P<tz_minute>[0-9][0-9]))?))?)?
    $~x
EOF;
	}

}