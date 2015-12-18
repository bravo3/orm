<?php
namespace Bravo3\ClassTools\Builder;

class CodeStyle
{
    const SCOPE_CLASS        = 'class';
    const SCOPE_FUNCTION     = 'function';
    const SCOPE_CONTROL      = 'control';
    const LINE_BREAK_UNIX    = "\n";
    const LINE_BREAK_WINDOWS = "\r\n";

    /**
     * @var bool
     */
    private $use_tabs;

    /**
     * @var int
     */
    private $tab_spaces;

    /**
     * @var array<string, bool>
     */
    private $brace_new_lines = [];

    /**
     * @var string
     */
    private $line_break_char;

    /**
     * @param bool   $use_tabs
     * @param int    $tab_spaces
     * @param string $line_break_char
     */
    public function __construct($use_tabs = false, $tab_spaces = 4, $line_break_char = self::LINE_BREAK_UNIX)
    {
        $this->use_tabs        = $use_tabs;
        $this->tab_spaces      = $tab_spaces;
        $this->line_break_char = $line_break_char;
        $this->brace_new_lines = [
            self::SCOPE_CLASS    => true,
            self::SCOPE_FUNCTION => true,
            self::SCOPE_CONTROL  => false,
        ];
    }

    /**
     * Get the use of tabs instead of spaces for indents
     *
     * @return boolean
     */
    public function getUseTabs()
    {
        return $this->use_tabs;
    }

    /**
     * Set the use of tabs instead of spaces for indents
     *
     * @param boolean $use_tabs
     * @return $this
     */
    public function setUseTabs($use_tabs)
    {
        $this->use_tabs = (bool)$use_tabs;
        return $this;
    }

    /**
     * Get the number of spaces to use for space indents
     *
     * @return int
     */
    public function getTabSpaces()
    {
        return $this->tab_spaces;
    }

    /**
     * Set the number of spaces to use for space indents
     *
     * @param int $tab_spaces
     * @return $this
     */
    public function setTabSpaces($tab_spaces)
    {
        $this->tab_spaces = max(0, $tab_spaces);
        return $this;
    }

    /**
     * Check if we should use a new line for a brace in a given scope
     *
     * @param string $scope
     * @return bool
     */
    public function getBraceNewLine($scope)
    {
        return isset($this->brace_new_lines[$scope]) ? $this->brace_new_lines[$scope] : false;
    }

    /**
     * Set if we should use a new line for a brace in a given scope
     *
     * @param string $scope
     * @param bool   $new_line
     * @return $this
     */
    public function setBraceNewLine($scope, $new_line)
    {
        $this->brace_new_lines[$scope] = (bool)$new_line;
        return $this;
    }

    /**
     * Get the characters for an appropriate code indent
     *
     * @param int $level
     * @return string
     */
    public function getIndent($level)
    {
        if ($level) {
            return str_repeat($this->use_tabs ? "\t" : (str_repeat(' ', $this->tab_spaces)), $level);
        } else {
            return '';
        }
    }

    /**
     * Get the line break character(s)
     *
     * @return string
     */
    public function getLineBreakChar()
    {
        return $this->line_break_char;
    }

    /**
     * Set the line break character(s)
     *
     * @param string $line_break_char
     * @return $this
     */
    public function setLineBreakChar($line_break_char)
    {
        $this->line_break_char = $line_break_char;
        return $this;
    }
}
