<?php
/**
 * Autoloader based on https://github.com/Nilpo/autoloader
 *
 * @package Autoloader
 * @author  Aayla Secura <aayla.secura.1138@gmail.com>
 * @license EULA + GPLv2
 * @link    https://github.com/aayla-secura/autoloader
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
     * A list of prefixes, one of which to require for each filename and ignore
     * in comparison.
     * E.g. if set to ["class-", "trait-"] then only filenames starting with
     * "class-" OR "trait-" will be considered, and "class-" will be stripped
     * from the beginning of the filename before comparing with symbol name.
     *
     * @var array<string>
     */
    private array $file_prefixes = [];

    /**
     * If true, symbol names are expected to be in camelCase or PascalCase and
     * are converted to snake_case before filename comparison.
     *
     * @var bool
     */
    private bool $uses_snake_case = false;

    /**
     * If true, underscores in resulting symbol names (after possibly converting
     * to snake_case) are replaced with dashes.
     *
     * @var bool
     */
    private bool $underscore_to_dash = false;

    /**
     * Whether to require files to have namespaces.
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
     * @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator>
     */
    private \RecursiveIteratorIterator $file_iterator;

    /**
     * Sets the extension for filenames to examine. Must include the leading
     * dot.
     *
     * @param string $dir
     *
     * @return void
     */
    public function set_top_dir(string $dir): void
    {
        $this->top_dir = $dir;
    }

    /**
     * Sets the extension for filenames to examine.
     *
     * @param string $ext
     *
     * @return void
     */
    public function set_file_ext(string $ext): void
    {
        $this->file_ext = $ext;
    }

    /**
     * Sets the prefix for filenames to examine.
     *
     * Equivalent to set_file_prefixes([$prefix])
     *
     * @param string $prefix
     *
     * @return void
     */
    public function set_file_prefix(string $prefix): void
    {
        $this->set_file_prefixes([$prefix]);
    }

    /**
     * Sets the prefixes for filenames to examine.
     *
     * @param array<string> $prefixes
     *
     * @return void
     */
    public function set_file_prefixes(array $prefixes): void
    {
        $this->file_prefixes = array_map(fn ($p) => strtolower($p), $prefixes);
    }

    /**
     * Assumes symbol names are camelCase or PascalCase and converts them to
     * snake_case.
     *
     * @param bool $use_dashes
     *
     * @return void
     */
    public function use_snake_case(bool $use_dashes = false): void
    {
        $this->uses_snake_case = true;
        if ($use_dashes) {
            $this->underscore_to_dash = true;
        }
    }

    /**
     * Replaces underscores with dashes in symbol names.
     *
     * @return void
     */
    public function use_dash_for_underscore(): void
    {
        $this->underscore_to_dash = true;
    }

    /**
     * Requires the use of namespaces.
     *
     * @param bool $strip_root
     *
     * @return void
     */
    public function use_namespaces(bool $strip_root = false): void
    {
        $this->uses_namespaces = true;
        $this->strip_root_namespace = $strip_root;
    }

    /**
     * Autoload function for registration with spl_autoload_register
     *
     * Looks recursively through project directory and loads symbol files based
     * on filename match.
     *
     * @param string $sym_name
     *
     * @return void
     */
    public function loader(string $sym_name): void
    {
        $tr_sym_name = $sym_name;
        if ($this->uses_snake_case) {
            $tr_sym_name = preg_replace(
                '/([A-Za-z])([A-Z](?=[a-z]))/',
                '$1_$2',
                $tr_sym_name
            );
            if ($tr_sym_name === null) {
                return;
            }
        }

        if ($this->underscore_to_dash) {
            $tr_sym_name = str_replace('_', '-', $tr_sym_name);
        }

        $files_needed = [];
        if ($this->uses_namespaces) {
            // We're using namespaces, so the relative path is the symbol name.
            // However we can't just load it straight away as filesystem may be
            // case sensitive.
            $sym_parts = explode('\\', $tr_sym_name);
            if (count($sym_parts) < 2) {
                return;
            }

            if ($this->strip_root_namespace) {
                $sym_parts = array_slice($sym_parts, 1);
            }

            $last_idx = count($sym_parts) - 1;
            $files_needed = array_map(
                fn ($pref) => $this->parts_to_filename(
                    // Prepend the file prefix to the last part which will become the base filename
                    array_replace($sym_parts, [$last_idx => $pref . $sym_parts[$last_idx]])
                ),
                $this->file_prefixes ? $this->file_prefixes : [""]
            );
        } else {
            $files_needed = array_map(
                fn ($pref) => strtolower($pref . $tr_sym_name . $this->file_ext),
                $this->file_prefixes ? $this->file_prefixes : [""]
            );
        }

        $directory = new \RecursiveDirectoryIterator(
            $this->top_dir,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );

        if (!isset($this->file_iterator)) {
            $this->file_iterator = new \RecursiveIteratorIterator(
                $directory,
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
        }

        foreach ($this->file_iterator as $file) {
            /**
             * @var \RecursiveDirectoryIterator $file
             */
            $name_to_compare = strtolower($file->getFilename());
            if ($this->uses_namespaces) {
                $curr_path = $this->file_iterator->getSubPath();
                if ($curr_path) {
                    $name_to_compare =  $curr_path . DIRECTORY_SEPARATOR . $name_to_compare;
                }
            }

            if (in_array($name_to_compare, $files_needed)) {
                if ($file->isReadable()) {
                    include_once $file->getPathname();

                }
                return;
            }
        }
    }

    /**
     * @param array<string> $sym_parts
     *
     * @return string
     */
    private function parts_to_filename(array $sym_parts): string
    {
        return strtolower(
            implode(
                DIRECTORY_SEPARATOR,
                $sym_parts
            ) . $this->file_ext
        );
    }
}
