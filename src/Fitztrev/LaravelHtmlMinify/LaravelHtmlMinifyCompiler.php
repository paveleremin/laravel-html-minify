<?php namespace Fitztrev\LaravelHtmlMinify;

use Illuminate\View\Compilers\BladeCompiler;

class LaravelHtmlMinifyCompiler extends BladeCompiler
{
    private $_config;

    public function __construct($config, $files, $cachePath)
    {
        parent::__construct($files, $cachePath);

        $this->_config = $config;

        // Add Minify to the list of compilers
        if ($this->_config['enabled'] === true) {
            $this->compilers[] = 'Minify';
        }

        // Set Blade contentTags and escapedContentTags
        $this->setContentTags(
            $this->_config['blade']['contentTags'][0],
            $this->_config['blade']['contentTags'][1]
        );

        $this->setEscapedContentTags(
            $this->_config['blade']['escapedContentTags'][0],
            $this->_config['blade']['escapedContentTags'][1]
        );

    }

    /**
    * We'll only compress a view if none of the following conditions are met.
    * 1) <pre> or <textarea> tags
    * 2) Embedded javascript (opening <script> tag not immediately followed
    * by </script>)
    * 3) Value attribute that contains 2 or more adjacent spaces
    *
    * @param string $value the contents of the view file
    *
    * @return bool
    */
    public function shouldMinify($value)
    {
        if (preg_match('/skipmin/', $value)
         || preg_match('/<(pre|textarea)/', $value)
         || preg_match('/<script[^\??>]*>[^<\/script>]/', $value)
         || preg_match('/value=("|\')(.*)([ ]{2,})(.*)("|\')/', $value)
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
    * Compress the HTML output before saving it
    *
    * @param string $value the contents of the view file
    *
    * @return string
    */
    protected function compileMinify($value)
    {
        if ($this->shouldMinify($value)) {
			$replace = array(
				'/<!--[^\[](.*?)[^\]]-->/s' => '',
				"/<\?php/" => '<?php ',
				"/\n([\S])/" => ' $1',
				"/\r/" => '',
				"/\n/" => '',
				"/\t/" => ' ',
				"/ +/" => ' ',
			);

			$value = preg_replace(
				array_keys($replace), array_values($replace), $value
			);

			$value = str_replace('value=""', '', $value);
			$value = str_replace('=""', '', $value);
			$value = preg_replace_callback('%(.*?)=\"(.*?)\"%', function ($m) {
				if (mb_strpos($m[2], 'php') !== false || mb_strpos($m[2], ' ') !== false) {
					return $m[0];
				}
				return $m[1].'='.$m[2];
			}, $value);

			// clean spaces
			$value = preg_replace('%\ {2,}%u', ' ', $value);
		}

		return $value;
    }

}
