<?php

namespace App\Libraries;

use Smalot\PdfParser\Parser;
use App\Models\SkillsModel;

class ResumeParser
{
    private $pdfParser;
    private $skillsModel;

    private $skillHeaders = [
        'skills','technical skills','core competencies',
        'expertise','technologies','proficiencies'
    ];

    public function __construct()
    {
        $this->pdfParser = new Parser();
        $this->skillsModel = new SkillsModel();
    }

    public function parse($filePath)
    {
        $rawText = $this->extractText($filePath);
        $cleanedText = $this->cleanText($rawText);

        $skillsSection = $this->extractSkillsSection($cleanedText);

        $matchedSkills = $this->matchSkills($skillsSection ?: $cleanedText);
        $contextualSkills = $this->extractContextualSkills($cleanedText);

        $allSkills = $this->mergeSkills($matchedSkills, $contextualSkills);

        return [
            'raw_text' => $rawText,
            'skills' => $allSkills,
            'sections' => $this->identifySections($cleanedText)
        ];
    }

    private function extractText($filePath)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            if ($ext === 'pdf') {
                return $this->pdfParser->parseFile($filePath)->getText();
            }

            if ($ext === 'docx') {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        $text .= $this->extractElementText($element) . "\n";
                    }
                }
                return $text;
            }

            if ($ext === 'doc' || $ext === 'rtf') {
                return strip_tags(file_get_contents($filePath));
            }

        } catch (\Exception $e) {
            log_message('error', 'Resume Parse Error: ' . $e->getMessage());
        }

        return '';
    }

    private function extractElementText($element)
    {
        $text = '';
        
        try {
            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $childElement) {
                    $text .= ' ' . $this->extractElementText($childElement);
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        foreach ($cell->getElements() as $cellElement) {
                            $text .= ' ' . $this->extractElementText($cellElement);
                        }
                    }
                    $text .= "\n";
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
                $text .= ' ' . $this->extractElementText($element->getTextObject());
            } elseif (method_exists($element, 'getText')) {
                $textValue = $element->getText();
                if (is_string($textValue)) {
                    $text .= ' ' . $textValue;
                }
            }
        } catch (\Exception $e) {
            // Skip problematic elements
        }
        
        return $text;
    }

    private function cleanText($text)
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[^\w\s\.\,\-\+\#\/\(\)]/', '', $text);
        return trim($text);
    }

    private function extractSkillsSection($text)
    {
        $textLower = strtolower($text);

        foreach ($this->skillHeaders as $header) {
            $pattern = '/' . preg_quote($header,'/') . '[\s\:\-]*(.*?)(?=education|experience|projects|employment|$)/is';

            if (preg_match($pattern, $textLower, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private function matchSkills($text)
    {
        $textLower = strtolower($text);
        $foundSkills = [];

        $skills = $this->skillsModel->findAll();

        foreach ($skills as $skill) {

            if ($this->containsWord($textLower, strtolower($skill['skill_name']))) {
                $foundSkills[] = [
                    'name' => $skill['skill_name'],
                    'category' => $skill['category']
                ];
                continue;
            }

            $aliases = json_decode($skill['aliases'], true) ?: [];

            foreach ($aliases as $alias) {
                if ($this->containsWord($textLower, strtolower($alias))) {
                    $foundSkills[] = [
                        'name' => $skill['skill_name'],
                        'category' => $skill['category']
                    ];
                    break;
                }
            }
        }

        return $foundSkills;
    }

    private function containsWord($text, $word)
    {
        return preg_match('/\b' . preg_quote($word,'/') . '\b/i', $text);
    }

    private function extractContextualSkills($text)
    {
        $keywords = [
            'proficient in','experienced with','knowledge of',
            'worked with','expertise in','skilled in',
            'familiar with','hands-on experience',
            'used','using','with','on'
        ];

        $skills = $this->skillsModel->findAll();
        $textLower = strtolower($text);
        $foundSkills = [];

        foreach ($keywords as $keyword) {
            $pattern = '/' . preg_quote($keyword,'/') . '\s*([^\.]{1,150})/i';

            if (preg_match_all($pattern, $textLower, $matches)) {
                foreach ($matches[1] as $segment) {
                    foreach ($skills as $skill) {

                        if ($this->containsWord($segment, strtolower($skill['skill_name']))) {
                            $foundSkills[] = [
                                'name' => $skill['skill_name'],
                                'category' => $skill['category']
                            ];
                        }

                        $aliases = json_decode($skill['aliases'], true) ?: [];

                        foreach ($aliases as $alias) {
                            if ($this->containsWord($segment, strtolower($alias))) {
                                $foundSkills[] = [
                                    'name' => $skill['skill_name'],
                                    'category' => $skill['category']
                                ];
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $foundSkills;
    }

    private function mergeSkills($dbSkills, $contextualSkills)
    {
        $merged = [];
        $seen = [];

        foreach (array_merge($dbSkills, $contextualSkills) as $skill) {
            $key = strtolower($skill['name']);
            if (!isset($seen[$key])) {
                $merged[] = $skill;
                $seen[$key] = true;
            }
        }

        return $merged;
    }

    private function identifySections($text)
    {
        $sections = ['education','experience','skills','projects','certifications','summary','objective','languages'];

        $found = [];
        $textLower = strtolower($text);

        foreach ($sections as $section) {
            if (preg_match('/\b'.$section.'\b/i', $textLower)) {
                $found[] = ucfirst($section);
            }
        }

        return $found;
    }
}
