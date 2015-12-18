<?php
namespace Bravo3\ClassTools\Builder;

class CodeBuilder
{
    /**
     * @var bool
     */
    protected $use_tabs;

    /**
     * @var int
     */
    protected $tab_spaces;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var int
     */
    protected $indent_level;

    /**
     * CodeBuilder constructor.
     * @param bool $use_tabs
     * @param int  $tab_spaces
     */
    public function __construct(bool $use_tabs = false, $tab_spaces = 4)
    {
        $this->use_tabs   = $use_tabs;
        $this->tab_spaces = $tab_spaces;
        $this->reset();
    }


    public function reset()
    {
        $this->code         = '';
        $this->indent_level = 0;
    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * Increase the code indent level
     *
     * @return $this
     */
    public function indent(): self
    {
        $this->indent_level++;
        return $this;
    }

    /**
     * Decrease the intend level
     *
     * @return ClassBuilder
     */
    public function unindent(): self
    {
        if ($this->indent_level < 0) {
            throw new \Exception("Cannot unindent past zero");
        }

        $this->indent_level--;

        return $this;
    }

    /**
     * Add a line to the output with optional white space
     *
     * @param string $line
     * @param int    $ws_pre
     * @param int    $ws_post
     * @return $this
     */
    public function addLine(string $line, int $ws_pre = 0, int $ws_post = 0): self
    {
        if ($this->indent_level) {
            $indent = str_repeat($this->use_tabs ? "\t" : (str_repeat(' ', $this->tab_spaces)), $this->indent_level);
        } else {
            $indent = '';
        }

        if ($ws_pre) {
            $this->code .= str_repeat(PHP_EOL, $ws_pre);
        }

        $this->code .= $indent.$line.PHP_EOL;

        if ($ws_post) {
            $this->code .= str_repeat(PHP_EOL, $ws_post);
        }

        return $this;
    }
}
