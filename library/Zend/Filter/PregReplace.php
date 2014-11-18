<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: PregReplace.php 24593 2012-01-05 20:35:02Z matthew $
 */

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

/**
 * @category   Zend
 * @package    Zend_Filter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Filter_PregReplace implements Zend_Filter_Interface
{
    /**
     * Pattern to match
     * @var mixed
     */
    protected $_matchPattern = null;

    /**
     * Replacement pattern
     * @var mixed
     */
    protected $_replacement = '';

    /**
     * Is unicode enabled?
     *
     * @var bool
     */
    static protected $_unicodeSupportEnabled = null;

    /**
     * Is Unicode Support Enabled Utility function
     *
     * @return bool
     */
    static public function isUnicodeSupportEnabled()
    {
        if (self::$_unicodeSupportEnabled === null) {
            self::_determineUnicodeSupport();
        }

        return self::$_unicodeSupportEnabled;
    }

    /**
     * Method to cache the regex needed to determine if unicode support is available
     *
     * @return bool
     */
    static protected function _determineUnicodeSupport()
    {
        self::$_unicodeSupportEnabled = (@preg_match('/\pL/u', 'a')) ? true : false;
    }

    /**
     * Constructor
     * Supported options are
     *     'match'   => matching pattern
     *     'replace' => replace with this
     *
     * @param  string|array $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp    = array();
            if (!empty($options)) {
                $temp['match'] = array_shift($options);
            }

            if (!empty($options)) {
                $temp['replace'] = array_shift($options);
            }

            $options = $temp;
        }

        if (array_key_exists('match', $options)) {
            $this->setMatchPattern($options['match']);
        }

        if (array_key_exists('replace', $options)) {
            $this->setReplacement($options['replace']);
        }
    }

    /**
     * Set the match pattern for the regex being called within filter()
     *
     * @param mixed $match - same as the first argument of preg_replace
     * @return Zend_Filter_PregReplace
     */
    public function setMatchPattern($match)
    {
        $this->_matchPattern = $match;
        return $this;
    }

    /**
     * Get currently set match pattern
     *
     * @return string
     */
    public function getMatchPattern()
    {
        return $this->_matchPattern;
    }

    /**
     * Set the Replacement pattern/string for the preg_replace called in filter
     *
     * @param mixed $replacement - same as the second argument of preg_replace
     * @return Zend_Filter_PregReplace
     */
    public function setReplacement($replacement)
    {
        $this->_replacement = $replacement;
        return $this;
    }

    /**
     * Get currently set replacement value
     *
     * @return string
     */
    public function getReplacement()
    {
        return $this->_replacement;
    }

    /**
     * Perform regexp replacement as filter
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        if ($this->_matchPattern == null) {
            require_once 'Zend/Filter/Exception.php';
            throw new Zend_Filter_Exception(get_class($this) . ' does not have a valid MatchPattern set.');
        }

        $pattern = is_array($this->_matchPattern)?implode(' ',$this->_matchPattern):$this->_matchPattern;
        if ( strpos($pattern,'#e') > -1 ) {
            foreach ( $this->_matchPattern as $index => $pattern ) {
                $replacement = $this->_replacement[$index];
                if ( strpos($pattern,'#e') > -1 ) {
                    $pattern = str_replace('#e','#',$pattern);
                    $replacement = function($m) use ($replacement) {
                        $replacement = str_replace('(\'\\', '($m[', $replacement);
                        $replacement = str_replace('\')', '])', $replacement);
                        return eval('return '.$replacement.';');
                    };
                }
                $value = preg_replace_callback($pattern, $replacement, $value);
            }
            return $value;
        }

        return preg_replace($this->_matchPattern, $this->_replacement, $value);
    }

}
