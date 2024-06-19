<?php

/**
 * @package SlimInteractions
 * @author  Aayla Secura <aayla.secura.1138@gmail.com>
 * @license EULA + GPLv2
 * @link    TODO
 *
 * Autoloader based on https://github.com/Nilpo/autoloader
 */

declare(strict_types=1);

/**
 * Looks for and loads class files. Note that filename comparison is always
 * case-insensitive and takes the first matching file.
 */
class Autoloader
{
    /**
     * The topmost directory where recursion should begin. Defaults to the current
     * directory.
     *
     * @var string
     */
    private string $top_dir = __DIR__;

    /**
     * File extension as a string. Defaults to ".php". Example is ".class.php"
     *
     * @var string
     */
    private string $file_ext = '.php';

    /**
     * A prefix to require for each filename and ignore in comparison.
     * E.g. if set to "class-" then only filenames starting with "class-" will
     * be considered, and "class-" will be stripped from the beginning of the
     * filename before comparing with class name.
     *
     * @var string
     */
    private string $file_prefix = "";

    /**
     * If true, class names are expected to be in camelCase or PascalCase and
     * are converted to snake_case before filename comparison.
     *
     * @var bool
     */
    private bool $uses_snake_case = false;

    /**
     * If true, underscores in resulting class names (after possibly converting
     * to snake_case) are replaced with dashes.
     *
     * @var bool
     */
    private bool $underscore_to_dash = false;

    /**
     * Whether to require classes to have namespaces.
     *
     * @var bool
     */
    private bool $uses_namespaces = false;

    /**
     * Whether the top namespace should be ignored when comparing path/filename.
     *
     * @var bool
     */
    private bool $strip_root_namespace = false;

    /**
     * A placeholder to hold the file iterator so that directory traversal is only
     * performed once.
     *
     * @var RecursiveIteratorIterator
     */
    private RecursiveIteratorIterator $file_iterator;

    /**
     * Sets the extension for filenames to examine. Must include the leading
     * dot.
     *
     * @param string $dir
     */
    public function set_top_dir(string $dir): void
    {
        $this->top_dir = $dir;
    }

    /**
     * Sets the extension for filenames to examine.
     *
     * @param string $ext
     */
    public function set_file_ext(string $ext): void
    {
        $this->file_ext = $ext;
    }

    /**
     * Sets the prefix for filenames to examine.
     *
     * @param string $prefix
     */
    public function set_file_prefix(string $prefix): void
    {
        $this->file_prefix = strtolower($prefix);
    }

    /**
     * Assumes class names are camelCase or PascalCase and converts them to
     * snake_case.
     *
     * @param bool $use_dashes
     */
    public function use_snake_case(bool $use_dashes = false): void
    {
        $this->uses_snake_case = true;
        if ($use_dashes) {
            $this->underscore_to_dash = true;
        }
    }

    /**
     * Replaces underscores with dashes in class names.
     */
    public function use_dash_for_underscore(): void
    {
        $this->underscore_to_dash = true;
    }

    /**
     * Requires the use of namespaces.
     *
     * @param bool $strip_root
     */
    public function use_namespaces(bool $strip_root = false): void
    {
        $this->uses_namespaces = true;
        $this->strip_root_namespace = $strip_root;
    }

    /**
     * Autoload function for registration with spl_autoload_register
     *
     * Looks recursively through project directory and loads class files based on
     * filename match.
     *
     * @param string $class_name
     */
    public function loader(string $class_name): void
    {
        $tr_class_name = $class_name;
        if ($this->uses_snake_case) {
            $tr_class_name = strtolower(
                preg_replace('/([A-Za-z])([A-Z](?=[a-z]))/', '$1_$2', $tr_class_name)
            );
        }

        if ($this->underscore_to_dash) {
            $tr_class_name = str_replace('_', '-', $tr_class_name);
        }

        if ($this->uses_namespaces) {
            // We're using namespaces, so the relative path is the class name.
            // However we can't just load it straight away as filesystem may be
            // case sensitive.
            $cls_parts = explode('\\', $tr_class_name);
            if (count($cls_parts) < 2) {
                return;
            }

            $cls_parts[count($cls_parts) - 1] = $this->file_prefix . $cls_parts[count($cls_parts) - 1];

            $idx = 0;
            if ($this->strip_root_namespace) {
                $idx = 1;
            }

            $file_needed = strtolower(implode(
                DIRECTORY_SEPARATOR,
                array_slice($cls_parts, $idx)
            ) . $this->file_ext);
        } else {
            $file_needed = strtolower($this->file_prefix . $tr_class_name . $this->file_ext);
        }

        $directory = new RecursiveDirectoryIterator(
            $this->top_dir,
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        if (!isset($this->file_iterator)) {
            $this->file_iterator = new RecursiveIteratorIterator(
                $directory,
                RecursiveIteratorIterator::LEAVES_ONLY
            );
        }

        foreach ($this->file_iterator as $file) {
            $name_to_compare = strtolower($file->getFilename());
            if ($this->uses_namespaces) {
                $curr_path = $this->file_iterator->getSubPath();
                if ($curr_path) {
                    $name_to_compare =  $curr_path . DIRECTORY_SEPARATOR . $name_to_compare;
                }
            }

            if ($file_needed === $name_to_compare) {
                if ($file->isReadable()) {
                    require_once $file->getPathname();

                }
                return;
            }
        }
    }
}
