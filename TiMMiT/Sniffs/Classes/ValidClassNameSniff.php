<?php

/**
 * Class names are PascalCase.
 * An abstract class starts with a lowercase a. A trait starts with a lowercase t.
 */

namespace PHP_CodeSniffer\Standards\TiMMiT\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\Tokens;

class ValidClassNameSniff implements Sniff
{
    /**
     * @return array<int|string>
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
            T_ENUM,
        ];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int                         $stackPtr
     *
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        $name = $this->getStructureName($phpcsFile, $stackPtr);
        if ($name === null) {
            return;
        }

        $isAbstract = $this->isAbstractClass($phpcsFile, $stackPtr);
        $isTrait = $tokens[$stackPtr]['code'] === T_TRAIT;
        if ($this->isValidName($name, $isAbstract, $isTrait)) {
            return;
        }

        $this->addNameError($phpcsFile, $stackPtr, $name, $isAbstract, $isTrait);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int                         $stackPtr
     *
     * @return string|null
     */
    private function getStructureName(File $phpcsFile, int $stackPtr): ?string
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['scope_opener'];
        $nameStart = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), $opener, true);
        if ($nameStart === false) {
            return null;
        }

        $nameEnd = $phpcsFile->findNext((Tokens::EMPTY_TOKENS + [T_COLON => T_COLON]), $nameStart, $opener);
        if ($nameEnd === false) {
            return $tokens[$nameStart]['content'];
        }

        return trim($phpcsFile->getTokensAsString($nameStart, ($nameEnd - $nameStart)));
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int                         $stackPtr
     *
     * @return bool
     */
    private function isAbstractClass(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_CLASS) {
            return false;
        }

        $modifier = $stackPtr;
        while (true) {
            $modifier = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($modifier - 1), null, true);
            if ($modifier === false) {
                return false;
            }

            $code = $tokens[$modifier]['code'];
            if ($code === T_ABSTRACT) {
                return true;
            }

            if ($this->isClassModifier($code) === false) {
                return false;
            }
        }
    }

    /**
     * PHPCS 4 uses string codes for tokens that are not native PHP tokens.
     *
     * @param int|string $code
     *
     * @return bool
     */
    private function isClassModifier(int|string $code): bool
    {
        if ($code === T_FINAL) {
            return true;
        }

        return $code === T_READONLY;
    }

    /**
     * @param string $name
     * @param bool   $isAbstract
     * @param bool   $isTrait
     *
     * @return bool
     */
    private function isValidName(string $name, bool $isAbstract, bool $isTrait): bool
    {
        if ($isAbstract) {
            return $this->hasPascalCaseSuffix($name, 'a');
        }

        if ($isTrait) {
            return $this->hasPascalCaseSuffix($name, 't');
        }

        return Common::isCamelCaps($name, true, true, false);
    }

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return bool
     */
    private function hasPascalCaseSuffix(string $name, string $prefix): bool
    {
        if (isset($name[1]) === false || $name[0] !== $prefix) {
            return false;
        }

        return Common::isCamelCaps(substr($name, 1), true, true, false);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int                         $stackPtr
     * @param string                      $name
     * @param bool                        $isAbstract
     * @param bool                        $isTrait
     *
     * @return void
     */
    private function addNameError(File $phpcsFile, int $stackPtr, string $name, bool $isAbstract, bool $isTrait): void
    {
        $tokens = $phpcsFile->getTokens();
        $opener = $tokens[$stackPtr]['scope_opener'];
        $nameStart = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), $opener, true);
        if ($nameStart === false) {
            $nameStart = $stackPtr;
        }

        if ($isAbstract) {
            $phpcsFile->addError(
                'Abstract class name "%s" must start with a lowercase "a" followed by PascalCase',
                $nameStart,
                'AbstractPrefix',
                [$name]
            );
            return;
        }

        if ($isTrait) {
            $phpcsFile->addError(
                'Trait name "%s" must start with a lowercase "t" followed by PascalCase',
                $nameStart,
                'TraitPrefix',
                [$name]
            );
            return;
        }

        $type = ucfirst($tokens[$stackPtr]['content']);
        $phpcsFile->addError(
            '%s name "%s" is not in PascalCase format',
            $nameStart,
            'NotPascalCase',
            [$type, $name]
        );
    }
}
