<?php

namespace App\Search;

class SearchGrammar
{
    protected $string;
    protected $position;
    protected $value;
    protected $cache;
    protected $cut;
    protected $errors;
    protected $warnings;

    protected function parseQuery()
    {
        $_position = $this->position;

        if (isset($this->cache['Query'][$_position])) {
            $_success = $this->cache['Query'][$_position]['success'];
            $this->position = $this->cache['Query'][$_position]['position'];
            $this->value = $this->cache['Query'][$_position]['value'];

            return $_success;
        }

        $_value7 = array();

        $_value3 = array();
        $_cut4 = $this->cut;

        while (true) {
            $_position2 = $this->position;

            $this->cut = false;
            $_value1 = array();

            $_success = $this->parse_();

            if ($_success) {
                $_value1[] = $this->value;

                $_success = $this->parseParameter();

                if ($_success) {
                    $r = $this->value;
                }
            }

            if ($_success) {
                $_value1[] = $this->value;

                $this->value = $_value1;
            }

            if ($_success) {
                $this->value = call_user_func(function () use (&$r) {
                    return $r;
                });
            }

            if (!$_success) {
                break;
            }

            $_value3[] = $this->value;
        }

        if (!$this->cut) {
            $_success = true;
            $this->position = $_position2;
            $this->value = $_value3;
        }

        $this->cut = $_cut4;

        if ($_success) {
            $r = $this->value;
        }

        if ($_success) {
            $_value7[] = $this->value;

            $_position5 = $this->position;
            $_cut6 = $this->cut;

            $this->cut = false;
            $_success = $this->parse_();

            if (!$_success && !$this->cut) {
                $_success = true;
                $this->position = $_position5;
                $this->value = null;
            }

            $this->cut = $_cut6;
        }

        if ($_success) {
            $_value7[] = $this->value;

            $this->value = $_value7;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$r, &$r) {
                return $r;
            });
        }

        $this->cache['Query'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Query');
        }

        return $_success;
    }

    protected function parseParameter()
    {
        $_position = $this->position;

        if (isset($this->cache['Parameter'][$_position])) {
            $_success = $this->cache['Parameter'][$_position]['success'];
            $this->position = $this->cache['Parameter'][$_position]['position'];
            $this->value = $this->cache['Parameter'][$_position]['value'];

            return $_success;
        }

        $_value14 = array();

        $_success = $this->parseIdentifier();

        if ($_success) {
            $name = $this->value;
        }

        if ($_success) {
            $_value14[] = $this->value;

            $_position8 = $this->position;
            $_cut9 = $this->cut;

            $this->cut = false;
            $_success = $this->parse_();

            if (!$_success && !$this->cut) {
                $_success = true;
                $this->position = $_position8;
                $this->value = null;
            }

            $this->cut = $_cut9;
        }

        if ($_success) {
            $_value14[] = $this->value;

            $_position10 = $this->position;
            $_cut11 = $this->cut;

            $this->cut = false;
            if (substr($this->string, $this->position, strlen(":")) === ":") {
                $_success = true;
                $this->value = substr($this->string, $this->position, strlen(":"));
                $this->position += strlen(":");
            } else {
                $_success = false;

                $this->report($this->position, '":"');
            }

            if (!$_success && !$this->cut) {
                $this->position = $_position10;

                if (substr($this->string, $this->position, strlen("~")) === "~") {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, strlen("~"));
                    $this->position += strlen("~");
                } else {
                    $_success = false;

                    $this->report($this->position, '"~"');
                }
            }

            $this->cut = $_cut11;

            if ($_success) {
                $t = $this->value;
            }
        }

        if ($_success) {
            $_value14[] = $this->value;

            $_position12 = $this->position;
            $_cut13 = $this->cut;

            $this->cut = false;
            $_success = $this->parse_();

            if (!$_success && !$this->cut) {
                $_success = true;
                $this->position = $_position12;
                $this->value = null;
            }

            $this->cut = $_cut13;
        }

        if ($_success) {
            $_value14[] = $this->value;

            $_success = $this->parseStr();

            if ($_success) {
                $value = $this->value;
            }
        }

        if ($_success) {
            $_value14[] = $this->value;

            $this->value = $_value14;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$name, &$t, &$value) {
                return array('type' => $t, 'name'=>$name, 'value'=>$value);
            });
        }

        $this->cache['Parameter'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Parameter');
        }

        return $_success;
    }

    protected function parseIdentifier()
    {
        $_position = $this->position;

        if (isset($this->cache['Identifier'][$_position])) {
            $_success = $this->cache['Identifier'][$_position]['success'];
            $this->position = $this->cache['Identifier'][$_position]['position'];
            $this->value = $this->cache['Identifier'][$_position]['value'];

            return $_success;
        }

        if (preg_match('/^[a-zA-Z0-9]$/', substr($this->string, $this->position, 1))) {
            $_success = true;
            $this->value = substr($this->string, $this->position, 1);
            $this->position += 1;
        } else {
            $_success = false;
        }

        if ($_success) {
            $_value16 = array($this->value);
            $_cut17 = $this->cut;

            while (true) {
                $_position15 = $this->position;

                $this->cut = false;
                if (preg_match('/^[a-zA-Z0-9]$/', substr($this->string, $this->position, 1))) {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, 1);
                    $this->position += 1;
                } else {
                    $_success = false;
                }

                if (!$_success) {
                    break;
                }

                $_value16[] = $this->value;
            }

            if (!$this->cut) {
                $_success = true;
                $this->position = $_position15;
                $this->value = $_value16;
            }

            $this->cut = $_cut17;
        }

        if ($_success) {
            $s = $this->value;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$s) {
                return implode('', $s);
            });
        }

        $this->cache['Identifier'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Identifier');
        }

        return $_success;
    }

    protected function parseStr()
    {
        $_position = $this->position;

        if (isset($this->cache['Str'][$_position])) {
            $_success = $this->cache['Str'][$_position]['success'];
            $this->position = $this->cache['Str'][$_position]['position'];
            $this->value = $this->cache['Str'][$_position]['value'];

            return $_success;
        }

        $_position23 = $this->position;
        $_cut24 = $this->cut;

        $this->cut = false;
        $_success = $this->parseIdentifier();

        if (!$_success && !$this->cut) {
            $this->position = $_position23;

            $_value22 = array();

            if (substr($this->string, $this->position, strlen("'")) === "'") {
                $_success = true;
                $this->value = substr($this->string, $this->position, strlen("'"));
                $this->position += strlen("'");
            } else {
                $_success = false;

                $this->report($this->position, '"\'"');
            }

            if ($_success) {
                $_value22[] = $this->value;

                $_success = $this->parseChars();

                if ($_success) {
                    $head = $this->value;
                }
            }

            if ($_success) {
                $_value22[] = $this->value;

                $_value20 = array();
                $_cut21 = $this->cut;

                while (true) {
                    $_position19 = $this->position;

                    $this->cut = false;
                    $_value18 = array();

                    $_success = $this->parse_();

                    if ($_success) {
                        $_value18[] = $this->value;

                        $_success = $this->parseChars();

                        if ($_success) {
                            $r = $this->value;
                        }
                    }

                    if ($_success) {
                        $_value18[] = $this->value;

                        $this->value = $_value18;
                    }

                    if ($_success) {
                        $this->value = call_user_func(function () use (&$head, &$r) {
                            return " ".$r;
                        });
                    }

                    if (!$_success) {
                        break;
                    }

                    $_value20[] = $this->value;
                }

                if (!$this->cut) {
                    $_success = true;
                    $this->position = $_position19;
                    $this->value = $_value20;
                }

                $this->cut = $_cut21;

                if ($_success) {
                    $tail = $this->value;
                }
            }

            if ($_success) {
                $_value22[] = $this->value;

                if (substr($this->string, $this->position, strlen("'")) === "'") {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, strlen("'"));
                    $this->position += strlen("'");
                } else {
                    $_success = false;

                    $this->report($this->position, '"\'"');
                }
            }

            if ($_success) {
                $_value22[] = $this->value;

                $this->value = $_value22;
            }

            if ($_success) {
                $this->value = call_user_func(function () use (&$head, &$r, &$tail) {
                    return $head.implode(' ', $tail);
                });
            }
        }

        $this->cut = $_cut24;

        $this->cache['Str'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Str');
        }

        return $_success;
    }

    protected function parseChars()
    {
        $_position = $this->position;

        if (isset($this->cache['Chars'][$_position])) {
            $_success = $this->cache['Chars'][$_position]['success'];
            $this->position = $this->cache['Chars'][$_position]['position'];
            $this->value = $this->cache['Chars'][$_position]['value'];

            return $_success;
        }

        if (preg_match('/^[^\']$/', substr($this->string, $this->position, 1))) {
            $_success = true;
            $this->value = substr($this->string, $this->position, 1);
            $this->position += 1;
        } else {
            $_success = false;
        }

        if ($_success) {
            $_value26 = array($this->value);
            $_cut27 = $this->cut;

            while (true) {
                $_position25 = $this->position;

                $this->cut = false;
                if (preg_match('/^[^\']$/', substr($this->string, $this->position, 1))) {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, 1);
                    $this->position += 1;
                } else {
                    $_success = false;
                }

                if (!$_success) {
                    break;
                }

                $_value26[] = $this->value;
            }

            if (!$this->cut) {
                $_success = true;
                $this->position = $_position25;
                $this->value = $_value26;
            }

            $this->cut = $_cut27;
        }

        if ($_success) {
            $s = $this->value;
        }

        if ($_success) {
            $this->value = call_user_func(function () use (&$s) {
                return implode('', $s);
            });
        }

        $this->cache['Chars'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, 'Chars');
        }

        return $_success;
    }

    protected function parse_()
    {
        $_position = $this->position;

        if (isset($this->cache['_'][$_position])) {
            $_success = $this->cache['_'][$_position]['success'];
            $this->position = $this->cache['_'][$_position]['position'];
            $this->value = $this->cache['_'][$_position]['value'];

            return $_success;
        }

        if (substr($this->string, $this->position, strlen(" ")) === " ") {
            $_success = true;
            $this->value = substr($this->string, $this->position, strlen(" "));
            $this->position += strlen(" ");
        } else {
            $_success = false;

            $this->report($this->position, '" "');
        }

        if ($_success) {
            $_value29 = array($this->value);
            $_cut30 = $this->cut;

            while (true) {
                $_position28 = $this->position;

                $this->cut = false;
                if (substr($this->string, $this->position, strlen(" ")) === " ") {
                    $_success = true;
                    $this->value = substr($this->string, $this->position, strlen(" "));
                    $this->position += strlen(" ");
                } else {
                    $_success = false;

                    $this->report($this->position, '" "');
                }

                if (!$_success) {
                    break;
                }

                $_value29[] = $this->value;
            }

            if (!$this->cut) {
                $_success = true;
                $this->position = $_position28;
                $this->value = $_value29;
            }

            $this->cut = $_cut30;
        }

        $this->cache['_'][$_position] = array(
            'success' => $_success,
            'position' => $this->position,
            'value' => $this->value
        );

        if (!$_success) {
            $this->report($_position, '_');
        }

        return $_success;
    }

    private function line()
    {
        return count(explode("\n", substr($this->string, 0, $this->position)));
    }

    private function rest()
    {
        return '"' . substr($this->string, $this->position) . '"';
    }

    protected function report($position, $expecting)
    {
        if ($this->cut) {
            $this->errors[$position][] = $expecting;
        } else {
            $this->warnings[$position][] = $expecting;
        }
    }

    private function expecting()
    {
        if (!empty($this->errors)) {
            ksort($this->errors);

            return end($this->errors)[0];
        }

        ksort($this->warnings);

        return implode(', ', end($this->warnings));
    }

    public function parse($_string)
    {
        $this->string = $_string;
        $this->position = 0;
        $this->value = null;
        $this->cache = array();
        $this->cut = false;
        $this->errors = array();
        $this->warnings = array();

        $_success = $this->parseQuery();

        if (!$_success) {
            throw new \InvalidArgumentException("Syntax error, expecting {$this->expecting()} on line {$this->line()}");
        }

        if ($this->position < strlen($this->string)) {
            throw new \InvalidArgumentException("Syntax error, unexpected {$this->rest()} on line {$this->line()}");
        }

        return $this->value;
    }
}