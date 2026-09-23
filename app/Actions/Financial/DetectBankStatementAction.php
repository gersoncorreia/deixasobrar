<?php

namespace App\Actions\Financial;

use App\Services\StatementParsers\BancoDoBrasilCsvParser;
use App\Services\StatementParsers\Contracts\BankStatementParserInterface;
use App\Services\StatementParsers\GenericFuzzyCsvParser;
use App\Services\StatementParsers\NubankCsvParser;
use App\Services\StatementParsers\UniversalOfxParser;

class DetectBankStatementAction
{
    /**
     * @var array<BankStatementParserInterface>
     */
    protected array $parsers;

    public function __construct()
    {
        $this->parsers = [
            new UniversalOfxParser(),
            new BancoDoBrasilCsvParser(),
            new NubankCsvParser(),
            new GenericFuzzyCsvParser(), // Universal fallback
        ];
    }

    public function execute(string $content, string $fileName = ''): BankStatementParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->canParse($content, $fileName)) {
                return $parser;
            }
        }

        return new GenericFuzzyCsvParser();
    }
}
