<?php
namespace Bravo3\ClassTools\Builder;

/**
 * A primitive service to build PHP code
 */
class CodeBuilder
{
    /**
     * @var CodeStyle
     */
    protected $code_style;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var int
     */
    protected $indent_level;

    /**
     * @param CodeStyle $code_style
     */
    public function __construct(CodeStyle $code_style = null)
    {
        $this->code_style = $code_style ?: new CodeStyle();
        $this->reset();
    }

    /**
     * Reset the code buffer
     */
    public function reset()
    {
        $this->code         = '';
        $this->indent_level = 0;
    }

    /**
     * Get the currently compiled code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get CodeStyle
     *
     * @return CodeStyle
     */
    public function getCodeStyle()
    {
        return $this->code_style;
    }

    /**
     * Set CodeStyle
     *
     * @param CodeStyle $code_style
     * @return $this
     */
    public function setCodeStyle(CodeStyle $code_style)
    {
        $this->code_style = $code_style;
        return $this;
    }

    /**
     * Increase the code indent level
     *
     * @return $this
     */
    public function indent()
    {
        $this->indent_level++;
        return $this;
    }

    /**
     * Decrease the intend level
     *
     * @return ClassBuilder
     */
    public function unindent()
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
     * @param string      $line
     * @param int         $ws_pre
     * @param int         $ws_post
     * @param bool|string $open_scope If a scope is provided, a brace will be added and indent level increased
     * @return $this
     */
    public function addLine($line, $ws_pre = 0, $ws_post = 0, $open_scope = false)
    {
        $indent = $this->getCodeStyle()->getIndent($this->indent_level);
        $nl     = $this->getCodeStyle()->getLineBreakChar();

        if ($ws_pre) {
            $this->code .= str_repeat($nl, $ws_pre);
        }

        $this->code .= $indent.$line;

        if ($open_scope) {
            if ($this->getCodeStyle()->getBraceNewLine($open_scope)) {
                $this->code .= $nl.$indent.'{'.$nl;
            } else {
                $this->code .= ' {'.$nl;
            }
            $this->indent_level++;
        } else {
            $this->code .= $nl;
        }

        if ($ws_post) {
            $this->code .= str_repeat($nl, $ws_post);
        }

        return $this;
    }

    /**
     * Reduce the indent level and add a closing brace;
     *
     * @param int $ws_pre
     * @param int $ws_post
     * @return $this
     */
    public function closeScope($ws_pre = 0, $ws_post = 0)
    {
        $nl = $this->getCodeStyle()->getLineBreakChar();

        if ($ws_pre) {
            $this->code .= str_repeat($nl, $ws_pre);
        }

        $this->unindent();
        $this->code .= $this->getCodeStyle()->getIndent($this->indent_level).'}'.$nl;

        if ($ws_post) {
            $this->code .= str_repeat($nl, $ws_post);
        }

        return $this;
    }
}
