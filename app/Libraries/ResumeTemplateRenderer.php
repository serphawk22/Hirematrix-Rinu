<?php

namespace App\Libraries;

class ResumeTemplateRenderer
{
    public function getTemplates(): array
    {
        return [
            'modern_professional' => [
                'label' => 'Modern Professional',
                'description' => 'Balanced modern layout with strong spacing, clean hierarchy, and ATS-friendly structure.',
                'tier' => 'free',
                'accent' => '#2563eb',
                'badge' => 'ATS Ready',
                'preview_class' => 'modern',
            ],
            'executive_sidebar' => [
                'label' => 'Executive Sidebar',
                'description' => 'Premium two-column resume with a strong branded sidebar for senior and polished profiles.',
                'tier' => 'free',
                'accent' => '#0f766e',
                'badge' => 'Leadership',
                'preview_class' => 'sidebar',
            ],
            'tech_compact' => [
                'label' => 'Tech Focus',
                'description' => 'Sharper technical layout with clear skill grouping and cleaner project-experience scanning.',
                'tier' => 'free',
                'accent' => '#059669',
                'badge' => 'Tech Focus',
                'preview_class' => 'tech',
            ],
        ];
    }

    public function getTemplateLabel(?string $templateKey): string
    {
        $templates = $this->getTemplates();
        $key = $this->normalizeTemplateKey($templateKey ?: 'modern_professional');

        return $templates[$key]['label'] ?? $templates['modern_professional']['label'];
    }

    public function decodeStoredContent(string $content, array $fallback = []): array
    {
        $content = trim($content);
        $decoded = json_decode($content, true);

        if (is_array($decoded) && isset($decoded['sections'])) {
        return [
            'template_key' => $this->normalizeTemplateKey((string) ($decoded['template_key'] ?? 'modern_professional')),
            'name' => (string) ($decoded['name'] ?? ($fallback['name'] ?? 'Candidate')),
            'target_role' => (string) ($decoded['target_role'] ?? ($fallback['target_role'] ?? '')),
            'summary' => (string) ($decoded['summary'] ?? ($fallback['summary'] ?? '')),
            'highlight_skills' => array_values(array_filter(array_map('trim', (array) ($decoded['highlight_skills'] ?? ($fallback['highlight_skills'] ?? []))))),
            'sections' => $decoded['sections'],
            'legacy_text' => '',
        ];
        }

        return [
            'template_key' => $this->normalizeTemplateKey('modern_professional'),
            'name' => (string) ($fallback['name'] ?? 'Candidate'),
            'target_role' => (string) ($fallback['target_role'] ?? ''),
            'summary' => (string) ($fallback['summary'] ?? ''),
            'highlight_skills' => array_values(array_filter(array_map('trim', (array) ($fallback['highlight_skills'] ?? [])))),
            'sections' => [],
            'legacy_text' => $content,
        ];
    }

    public function renderPreview(string $content, array $fallback = []): string
    {
        $resume = $this->decodeStoredContent($content, $fallback);
        if ($resume['legacy_text'] !== '') {
            return '<div style="font-family:helvetica;font-size:10pt;line-height:1.5;">'
                . nl2br(esc($resume['legacy_text']))
                . '</div>';
        }

        return $this->renderPdfHtml($resume);
    }

    public function renderDocument(string $content, array $fallback = []): string
    {
        $resume = $this->decodeStoredContent($content, $fallback);
        if ($resume['legacy_text'] !== '') {
            return '<!doctype html><html><head><meta charset="utf-8"><title>Resume</title></head><body><pre style="white-space:pre-wrap;font-family:Arial,sans-serif;">'
                . esc($resume['legacy_text'])
                . '</pre></body></html>';
        }

        return '<!doctype html><html><head><meta charset="utf-8"><title>'
            . esc(($resume['name'] ?: 'Candidate') . ' Resume')
            . '</title></head><body style="margin:40px 60px;font-family:helvetica;background:#fff;">'
            . $this->renderPdfHtml($resume)
            . '</body></html>';
    }

    public function createPdfFile(string $content, array $fallback, string $filenameBase): string
    {
        $resume = $this->decodeStoredContent($content, $fallback);
        $filename = $this->sanitizeFilename($filenameBase) . '.pdf';
        $directory = WRITEPATH . 'uploads/resume_versions/';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = $directory . $filename;

        if ($this->createBrowserPdfFile($resume, $filepath, $filenameBase)) {
            return $filepath;
        }

        require_once APPPATH . '../vendor/autoload.php';

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('AI Job Portal');
        $pdf->SetAuthor('AI Job Portal');
        $pdf->SetTitle($filenameBase);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(18, 18, 18);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($this->renderPdfHtml($resume), true, false, true, false, '');
        $pdf->Output($filepath, 'F');

        return $filepath;
    }

    private function createBrowserPdfFile(array $resume, string $pdfPath, string $filenameBase): bool
    {
        $browserPath = $this->findBrowserPdfExecutable();
        if ($browserPath === null) {
            return false;
        }

        $tempDir = WRITEPATH . 'uploads/resume_versions/browser_pdf/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $htmlPath = $tempDir . $this->sanitizeFilename($filenameBase) . '.html';
        file_put_contents($htmlPath, $this->renderDocument(json_encode($resume, JSON_UNESCAPED_UNICODE), [
            'name' => (string) ($resume['name'] ?? 'Candidate'),
            'target_role' => (string) ($resume['target_role'] ?? ''),
            'summary' => (string) ($resume['summary'] ?? ''),
            'highlight_skills' => (array) ($resume['highlight_skills'] ?? []),
        ]));

        $fileUrl = 'file:///' . str_replace(DIRECTORY_SEPARATOR, '/', ltrim($htmlPath, '\\/'));
        $command = escapeshellarg($browserPath)
            . ' --headless=new --disable-gpu --allow-file-access-from-files --print-to-pdf-no-header --no-pdf-header-footer'
            . ' --print-to-pdf=' . escapeshellarg($pdfPath)
            . ' ' . escapeshellarg($fileUrl);

        @exec($command, $output, $status);

        if ($status === 0 && is_file($pdfPath) && filesize($pdfPath) > 0) {
            @unlink($htmlPath);
            return true;
        }

        @unlink($htmlPath);
        return false;
    }

    private function findBrowserPdfExecutable(): ?string
    {
        $candidates = array_filter([
            env('resume.chromePath'),
            env('RESUME_PDF_CHROME_PATH'),
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/bin/microsoft-edge',
        ]);

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function renderPdfHtml(array $resume): string
    {
        if ($resume['legacy_text'] !== '') {
            return '<div style="font-family:helvetica;font-size:10pt;line-height:1.5;">'
                . nl2br(esc($resume['legacy_text']))
                . '</div>';
        }

        $templateKey = $this->resolveBestTemplateForContent($resume);
        $template = $this->getTemplates()[$templateKey] ?? $this->getTemplates()['modern_professional'];
        $accent = (string) ($template['accent'] ?? '#2563eb');
        $sections = (array) ($resume['sections'] ?? []);

        if ($templateKey === 'executive_sidebar') {
            return $this->renderExecutiveSidebarPdf($resume, $sections, $accent);
        }

        if ($templateKey === 'tech_compact') {
            return $this->renderTechCompactPdf($resume, $sections, $accent);
        }

        return $this->renderDefaultPdf($resume, $sections, $accent);
    }

    private function renderDefaultPdf(array $resume, array $sections, string $accent): string
    {
        $html = '';
        $html .= '<div style="font-family:helvetica;color:#0f172a;">';
        $html .= $this->renderPdfHeader($resume, $accent, false);

        if (trim((string) ($resume['summary'] ?? '')) !== '') {
            $html .= $this->renderPdfSectionTitle('Professional Summary', $accent, false);
            $html .= '<div style="font-size:10.2pt;line-height:1.7;color:#334155;margin-top:6px;">'
                . nl2br(esc((string) $resume['summary']))
                . '</div>';
        }

        if (!empty($sections['skills']['groups'])) {
            $html .= $this->renderPdfSkills((array) $sections['skills'], $accent, false);
        }

        foreach (['experience', 'projects', 'education', 'certifications'] as $key) {
            if (empty($sections[$key]['items'])) {
                continue;
            }

            $html .= $this->renderPdfItemsSection(
                (string) ($sections[$key]['title'] ?? ucfirst($key)),
                (array) $sections[$key]['items'],
                $accent,
                false
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function renderExecutiveSidebarPdf(array $resume, array $sections, string $accent): string
    {
        $sidebarBlocks = '';

        if (trim((string) ($resume['summary'] ?? '')) !== '') {
            $sidebarBlocks .= '<div style="font-size:10pt;line-height:1.72;color:#e2e8f0;margin-top:2px;">'
                . nl2br(esc((string) $resume['summary']))
                . '</div>';
        }

        if (!empty($sections['skills']['groups'])) {
            $sidebarBlocks .= $this->renderSidebarPdfSkills((array) $sections['skills']);
        } elseif (!empty($resume['highlight_skills'])) {
            $sidebarBlocks .= $this->renderSidebarPdfNamedList('Core Skills', (array) $resume['highlight_skills']);
        }

        if (!empty($sections['education']['items'])) {
            $sidebarBlocks .= $this->renderSidebarPdfCompactList((string) ($sections['education']['title'] ?? 'Education'), (array) $sections['education']['items']);
        }

        if (!empty($sections['certifications']['items'])) {
            $sidebarBlocks .= $this->renderSidebarPdfCompactList((string) ($sections['certifications']['title'] ?? 'Certifications'), (array) $sections['certifications']['items']);
        }

        $main = '';
        foreach (['experience', 'projects'] as $key) {
            if (empty($sections[$key]['items'])) {
                continue;
            }

            $main .= $this->renderPdfItemsSection(
                (string) ($sections[$key]['title'] ?? ucfirst($key)),
                (array) ($sections[$key]['items'] ?? []),
                $accent,
                false
            );
        }

        return '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="font-family:helvetica;">'
            . '<tr>'
            . '<td width="33%" valign="top" style="background-color:#0f172a;color:#ffffff;padding:18px 16px;border-radius:14px;">'
            . '<div style="font-size:23pt;font-weight:bold;line-height:1.05;color:#ffffff;">' . esc((string) ($resume['name'] ?? 'Candidate')) . '</div>'
            . (trim((string) ($resume['target_role'] ?? '')) !== ''
                ? '<div style="font-size:10.5pt;font-weight:bold;color:#99f6e4;letter-spacing:1.8px;text-transform:uppercase;margin-top:8px;">' . esc((string) $resume['target_role']) . '</div>'
                : '')
            . $sidebarBlocks
            . '</td>'
            . '<td width="4%"></td>'
            . '<td width="63%" valign="top" style="padding-top:2px;">'
            . $main
            . '</td>'
            . '</tr>'
            . '</table>';
    }

    private function renderTechCompactPdf(array $resume, array $sections, string $accent): string
    {
        $html = '';
        $html .= '<div style="font-family:helvetica;color:#0f172a;">';
        $html .= $this->renderPdfHeader($resume, $accent, true);

        if (trim((string) ($resume['summary'] ?? '')) !== '') {
            $html .= '<div style="margin:4px 0 12px 14px;font-size:10pt;line-height:1.72;color:#334155;">'
                . nl2br(esc((string) $resume['summary']))
                . '</div>';
        }

        if (!empty($sections['skills']['groups'])) {
            $html .= $this->renderPdfSkills((array) $sections['skills'], $accent, true);
        }

        foreach (['experience', 'projects', 'education', 'certifications'] as $key) {
            if (empty($sections[$key]['items'])) {
                continue;
            }

            $html .= $this->renderPdfItemsSection(
                (string) ($sections[$key]['title'] ?? ucfirst($key)),
                (array) $sections[$key]['items'],
                $accent,
                true
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function renderPdfHeader(array $resume, string $accent, bool $withAccentBar): string
    {
        $html = '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">';
        $html .= '<tr>';
        if ($withAccentBar) {
            $html .= '<td width="2%" style="border-left:4px solid ' . esc($accent) . ';">&nbsp;</td>';
            $html .= '<td width="98%" style="padding-left:12px;">';
        } else {
            $html .= '<td width="70%">';
        }
        $html .= '<div style="font-size:26pt;font-weight:bold;color:#0f172a;line-height:1.05;">' . esc((string) ($resume['name'] ?? 'Candidate')) . '</div>';
        if (trim((string) ($resume['target_role'] ?? '')) !== '') {
            $html .= '<div style="font-size:11pt;font-weight:bold;color:' . esc($accent) . ';letter-spacing:2px;text-transform:uppercase;margin-top:6px;">'
                . esc((string) $resume['target_role'])
                . '</div>';
        }
        $html .= '</td>';
        if (!$withAccentBar) {
            $html .= '<td width="30%" align="right" style="font-size:9.5pt;color:#475569;">';
            if (!empty($resume['highlight_skills'])) {
                $html .= esc(implode(' | ', (array) $resume['highlight_skills']));
            } else {
                $html .= '&nbsp;';
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }

    private function renderSidebarPdfSectionTitle(string $title): string
    {
        return '<div style="font-size:9.2pt;font-weight:bold;letter-spacing:1.6px;text-transform:uppercase;color:#99f6e4;margin-top:16px;margin-bottom:8px;">'
            . esc($title)
            . '</div>';
    }

    private function renderSidebarPdfSkills(array $section): string
    {
        $groups = (array) ($section['groups'] ?? []);
        if ($groups === []) {
            return '';
        }

        $html = $this->renderSidebarPdfSectionTitle((string) ($section['title'] ?? 'Technical Skills'));
        foreach ($groups as $group) {
            $label = trim((string) ($group['label'] ?? ''));
            $items = array_values(array_filter(array_map('trim', (array) ($group['items'] ?? []))));
            if ($label === '' || $items === []) {
                continue;
            }

            $html .= '<div style="font-size:9.2pt;font-weight:bold;color:#ffffff;margin-top:8px;">' . esc($label) . '</div>';
            $html .= '<div style="font-size:9.6pt;line-height:1.65;color:#cbd5e1;margin-top:2px;">' . esc(implode(' | ', $items)) . '</div>';
        }

        return $html;
    }

    private function renderSidebarPdfCompactList(string $title, array $items): string
    {
        if ($items === []) {
            return '';
        }

        $html = $this->renderSidebarPdfSectionTitle($title);
        foreach ($items as $item) {
            $headline = trim((string) ($item['headline'] ?? ''));
            $subhead = trim((string) ($item['subhead'] ?? ''));
            $meta = trim((string) ($item['meta'] ?? ''));
            if ($headline !== '') {
                $html .= '<div style="font-size:10pt;font-weight:bold;color:#ffffff;margin-top:8px;">' . esc($headline) . '</div>';
            }
            if ($subhead !== '') {
                $html .= '<div style="font-size:9.4pt;color:#cbd5e1;margin-top:2px;">' . esc($subhead) . '</div>';
            }
            if ($meta !== '') {
                $html .= '<div style="font-size:8.9pt;color:#94a3b8;margin-top:2px;">' . esc($meta) . '</div>';
            }
        }

        return $html;
    }

    private function renderSidebarPdfNamedList(string $title, array $items): string
    {
        $items = array_values(array_filter(array_map('trim', $items)));
        if ($items === []) {
            return '';
        }

        return $this->renderSidebarPdfSectionTitle($title)
            . '<div style="font-size:9.6pt;line-height:1.7;color:#cbd5e1;">'
            . esc(implode(' | ', $items))
            . '</div>';
    }

    private function renderPdfSectionTitle(string $title, string $accent, bool $filled): string
    {
        if ($filled) {
            return '<div style="margin-top:14px;margin-bottom:8px;background-color:#eaf7f1;color:' . esc($accent) . ';font-size:10.2pt;font-weight:bold;letter-spacing:2px;text-transform:uppercase;padding:8px 12px;border-radius:4px;">'
                . esc($title)
                . '</div>';
        }

        return '<div style="margin-top:14px;margin-bottom:6px;font-size:11pt;font-weight:bold;letter-spacing:.4px;color:#0f172a;text-transform:uppercase;border-bottom:1px solid #dbe3ee;padding-bottom:3px;">'
            . esc($title)
            . '</div>';
    }

    private function renderPdfSkills(array $section, string $accent, bool $filledHeading): string
    {
        $groups = (array) ($section['groups'] ?? []);
        if (empty($groups)) {
            return '';
        }

        $html = $this->renderPdfSectionTitle((string) ($section['title'] ?? 'Technical Skills'), $accent, $filledHeading);
        $html .= '<table cellpadding="6" cellspacing="0" border="0" width="100%">';
        foreach ($groups as $group) {
            $label = trim((string) ($group['label'] ?? ''));
            $items = array_values(array_filter(array_map('trim', (array) ($group['items'] ?? []))));
            if ($label === '' || empty($items)) {
                continue;
            }

            $html .= '<tr>';
            $html .= '<td width="28%" valign="top" style="font-size:9.5pt;font-weight:bold;color:' . esc($accent) . ';border-bottom:1px solid #eef2f7;">' . esc($label) . '</td>';
            $html .= '<td width="72%" valign="top" style="font-size:9.8pt;color:#334155;border-bottom:1px solid #eef2f7;">' . esc(implode(' | ', $items)) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }

    private function renderPdfItemsSection(string $title, array $items, string $accent, bool $filledHeading): string
    {
        if (empty($items)) {
            return '';
        }

        $html = $this->renderPdfSectionTitle($title, $accent, $filledHeading);
        foreach ($items as $item) {
            $headline = trim((string) ($item['headline'] ?? ''));
            $subhead = trim((string) ($item['subhead'] ?? ''));
            $meta = trim((string) ($item['meta'] ?? ''));
            $bullets = array_values(array_filter(array_map('trim', (array) ($item['bullets'] ?? []))));

            $html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:10px;">';
            $html .= '<tr>';
            $html .= '<td width="64%" valign="top" style="font-size:12pt;font-weight:bold;color:#111827;">' . esc($headline) . '</td>';
            $html .= '<td width="36%" valign="top" align="right" style="font-size:9.4pt;color:#64748b;">' . esc($meta) . '</td>';
            $html .= '</tr>';
            if ($subhead !== '') {
                $html .= '<tr><td colspan="2" style="font-size:10pt;color:' . esc($accent) . ';font-weight:bold;padding-top:4px;">' . esc($subhead) . '</td></tr>';
            }
            $html .= '</table>';

            if (!empty($bullets)) {
                $html .= '<ul style="margin-top:6px;margin-bottom:10px;padding-left:16px;">';
                foreach ($bullets as $bullet) {
                    $html .= '<li style="font-size:10pt;line-height:1.68;color:#334155;margin-bottom:4px;">' . esc($bullet) . '</li>';
                }
                $html .= '</ul>';
            }
        }

        return $html;
    }

    private function renderHtml(array $resume, bool $fullDocument): string
    {
        $templateKey = $this->resolveBestTemplateForContent($resume);

        if ($templateKey === 'executive_sidebar') {
            return $this->renderExecutiveSidebar($resume, $fullDocument);
        }

        if ($templateKey === 'tech_compact') {
            return $this->renderTechCompact($resume, $fullDocument);
        }

        return $this->renderModernProfessional($resume, $fullDocument);
    }

    private function renderModernProfessional(array $resume, bool $fullDocument): string
    {
        $sections = $resume['sections'];
        $skills = $resume['highlight_skills'];

        $html = '<div class="resume-template-shell template-modern">';
        $html .= '<div class="resume-hero">';
        $html .= '<div><div class="resume-name">' . esc($resume['name']) . '</div>';
        if ($resume['target_role'] !== '') {
            $html .= '<div class="resume-role">' . esc($resume['target_role']) . '</div>';
        }
        $html .= '</div>';
        $html .= '<div class="resume-chip-row">' . $this->renderSkillChips($skills) . '</div>';
        $html .= '</div>';
        $html .= $this->renderSummaryBlock($resume['summary']);
        if (!empty($sections['skills']['groups'])) {
            $html .= $this->renderSkillGroupsSection((string) ($sections['skills']['title'] ?? 'Technical Skills'), (array) $sections['skills']['groups']);
        }
        $html .= $this->renderSectionList($sections, ['experience', 'projects', 'education', 'certifications']);
        $html .= '</div>';

        return $html;
    }

    private function resolveBestTemplateForContent(array $resume): string
    {
        $templateKey = $this->normalizeTemplateKey((string) ($resume['template_key'] ?? 'modern_professional'));
        $sections = (array) ($resume['sections'] ?? []);
        $experienceCount = count((array) ($sections['experience']['items'] ?? []));
        $projectCount = count((array) ($sections['projects']['items'] ?? []));
        $educationCount = count((array) ($sections['education']['items'] ?? []));
        $certificationCount = count((array) ($sections['certifications']['items'] ?? []));

        if ($templateKey === 'executive_sidebar') {
            $hasThinMainColumn = $experienceCount === 0 && $projectCount <= 1;
            $hasFresherProfile = $experienceCount === 0 && ($educationCount > 0 || $projectCount > 0 || $certificationCount > 0);

            if ($hasThinMainColumn || $hasFresherProfile) {
                return $projectCount > 0 ? 'tech_compact' : 'modern_professional';
            }
        }

        return $templateKey;
    }

    private function renderExecutiveSidebar(array $resume, bool $fullDocument): string
    {
        $sections = $resume['sections'];
        $skills = $resume['highlight_skills'];
        $education = $sections['education']['items'] ?? [];
        $certifications = $sections['certifications']['items'] ?? [];

        $html = '<div class="resume-template-shell template-sidebar">';
        $html .= '<div class="resume-sidebar">';
        $html .= '<div class="resume-name">' . esc($resume['name']) . '</div>';
        if ($resume['target_role'] !== '') {
            $html .= '<div class="resume-role">' . esc($resume['target_role']) . '</div>';
        }
        $html .= $this->renderSummaryBlock($resume['summary']);
        $html .= !empty($sections['skills']['groups'])
            ? $this->renderSkillGroupsSection((string) ($sections['skills']['title'] ?? 'Technical Skills'), (array) $sections['skills']['groups'])
            : $this->renderNamedList('Technical Skills', $skills);
        $html .= $this->renderCompactList('Education', $education);
        $html .= $this->renderCompactList('Certifications', $certifications);
        $html .= '</div>';
        $html .= '<div class="resume-main">';
        $html .= $this->renderSectionList($sections, ['experience', 'projects']);
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderMinimalTimeline(array $resume, bool $fullDocument): string
    {
        $sections = $resume['sections'];
        $html = '<div class="resume-template-shell template-timeline">';
        $html .= '<div class="resume-name">' . esc($resume['name']) . '</div>';
        if ($resume['target_role'] !== '') {
            $html .= '<div class="resume-role">' . esc($resume['target_role']) . '</div>';
        }
        $html .= $this->renderSummaryBlock($resume['summary']);
        if (!empty($sections['skills']['groups'])) {
            $html .= $this->renderSkillGroupsSection((string) ($sections['skills']['title'] ?? 'Technical Skills'), (array) $sections['skills']['groups']);
        } else {
            $html .= $this->renderNamedList('Technical Skills', $resume['highlight_skills']);
        }
        $html .= $this->renderTimelineSection($sections['experience']['title'] ?? 'Professional Experience', $sections['experience']['items'] ?? []);
        $html .= $this->renderTimelineSection($sections['projects']['title'] ?? 'Projects', $sections['projects']['items'] ?? []);
        $html .= $this->renderCompactList($sections['education']['title'] ?? 'Education', $sections['education']['items'] ?? []);
        $html .= $this->renderCompactList($sections['certifications']['title'] ?? 'Certifications', $sections['certifications']['items'] ?? []);
        $html .= '</div>';

        return $html;
    }

    private function renderCreativeBold(array $resume, bool $fullDocument): string
    {
        $sections = $resume['sections'];
        $html = '<div class="resume-template-shell template-creative">';
        $html .= '<div class="creative-header">';
        $html .= '<div class="resume-name">' . esc($resume['name']) . '</div>';
        if ($resume['target_role'] !== '') {
            $html .= '<div class="resume-role">' . esc($resume['target_role']) . '</div>';
        }
        $html .= '</div>';
        $html .= '<div class="resume-chip-row">' . $this->renderSkillChips($resume['highlight_skills']) . '</div>';
        $html .= $this->renderSummaryBlock($resume['summary']);
        $html .= $this->renderSectionList($sections, ['experience', 'projects', 'skills', 'education', 'certifications']);
        $html .= '</div>';
        return $html;
    }

    private function renderTechCompact(array $resume, bool $fullDocument): string
    {
        $sections = $resume['sections'];
        $html = '<div class="resume-template-shell template-tech">';
        $html .= '<div class="tech-header">';
        $html .= '<div class="resume-name">' . esc($resume['name']) . '</div>';
        if ($resume['target_role'] !== '') {
            $html .= '<div class="resume-role">' . esc($resume['target_role']) . '</div>';
        }
        $html .= '</div>';
        if (!empty($sections['skills']['groups'])) {
            $html .= $this->renderSkillGroupsSection((string) ($sections['skills']['title'] ?? 'Technical Skills'), (array) $sections['skills']['groups']);
        }
        $html .= $this->renderSectionList($sections, ['experience', 'projects', 'education', 'certifications']);
        $html .= '</div>';
        return $html;
    }

    private function renderElegantClassic(array $resume, bool $fullDocument): string
    {
        $sections = $resume['sections'];
        $html = '<div class="resume-template-shell template-classic">';
        $html .= '<div class="classic-header">';
        $html .= '<div class="resume-name">' . esc($resume['name']) . '</div>';
        if ($resume['target_role'] !== '') {
            $html .= '<div class="resume-role">' . esc($resume['target_role']) . '</div>';
        }
        $html .= '</div>';
        $html .= $this->renderSummaryBlock($resume['summary']);
        $html .= $this->renderSectionList($sections, ['experience', 'skills', 'education', 'certifications', 'projects']);
        $html .= '</div>';
        return $html;
    }

    private function renderSummaryBlock(string $summary): string
    {
        if (trim($summary) === '') {
            return '';
        }

        return '<div class="resume-summary">' . nl2br(esc($summary)) . '</div>';
    }

    private function renderSectionList(array $sections, array $order): string
    {
        $html = '';
        foreach ($order as $key) {
            if (empty($sections[$key])) {
                continue;
            }

            $section = $sections[$key];
            if (!empty($section['items'])) {
                $html .= $this->renderItemsSection((string) ($section['title'] ?? ucfirst($key)), (array) $section['items']);
            } elseif (!empty($section['body'])) {
                $html .= $this->renderTextSection((string) ($section['title'] ?? ucfirst($key)), (string) $section['body']);
            }
        }

        return $html;
    }

    private function renderItemsSection(string $title, array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<section class="resume-section"><h3>' . esc($title) . '</h3>';
        foreach ($items as $item) {
            $headline = trim((string) ($item['headline'] ?? ''));
            $subhead = trim((string) ($item['subhead'] ?? ''));
            $meta = trim((string) ($item['meta'] ?? ''));
            $bullets = array_values(array_filter(array_map('trim', (array) ($item['bullets'] ?? []))));

            $html .= '<div class="resume-item">';
            if ($headline !== '' || $subhead !== '') {
                $html .= '<div class="resume-item-head">';
                $html .= '<div class="resume-item-title">' . esc($headline) . '</div>';
                if ($subhead !== '') {
                    $html .= '<div class="resume-item-subhead">' . esc($subhead) . '</div>';
                }
                $html .= '</div>';
            }
            if ($meta !== '') {
                $html .= '<div class="resume-item-meta">' . esc($meta) . '</div>';
            }
            if (!empty($bullets)) {
                $html .= '<ul>';
                foreach ($bullets as $bullet) {
                    $html .= '<li>' . esc($bullet) . '</li>';
                }
                $html .= '</ul>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        return $html;
    }

    private function renderTimelineSection(string $title, array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<section class="resume-section"><h3>' . esc($title) . '</h3><div class="timeline">';
        foreach ($items as $item) {
            $html .= '<div class="timeline-item">';
            $html .= '<div class="timeline-dot"></div><div class="timeline-body">';
            $html .= '<div class="resume-item-title">' . esc((string) ($item['headline'] ?? '')) . '</div>';
            if (!empty($item['subhead'])) {
                $html .= '<div class="resume-item-subhead">' . esc((string) $item['subhead']) . '</div>';
            }
            if (!empty($item['meta'])) {
                $html .= '<div class="resume-item-meta">' . esc((string) $item['meta']) . '</div>';
            }
            if (!empty($item['bullets'])) {
                $html .= '<ul>';
                foreach ((array) $item['bullets'] as $bullet) {
                    $html .= '<li>' . esc((string) $bullet) . '</li>';
                }
                $html .= '</ul>';
            }
            $html .= '</div></div>';
        }
        $html .= '</div></section>';

        return $html;
    }

    private function renderCompactList(string $title, array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<section class="resume-section compact-section"><h3>' . esc($title) . '</h3><div class="compact-list">';
        foreach ($items as $item) {
            $line = trim(implode(' | ', array_filter([
                (string) ($item['headline'] ?? ''),
                (string) ($item['subhead'] ?? ''),
                (string) ($item['meta'] ?? ''),
            ])));
            if ($line !== '') {
                $html .= '<div>' . esc($line) . '</div>';
            }
        }
        $html .= '</div></section>';

        return $html;
    }

    private function renderNamedList(string $title, array $items): string
    {
        $items = array_values(array_filter(array_map('trim', $items)));
        if (empty($items)) {
            return '';
        }

        return '<section class="resume-section compact-section"><h3>' . esc($title) . '</h3><div class="resume-chip-row">'
            . $this->renderSkillChips($items)
            . '</div></section>';
    }

    private function renderSkillGroupsSection(string $title, array $groups): string
    {
        if (empty($groups)) {
            return '';
        }

        $html = '<section class="resume-section"><h3>' . esc($title) . '</h3><div class="skill-groups">';
        foreach ($groups as $group) {
            $label = trim((string) ($group['label'] ?? ''));
            $items = array_values(array_filter(array_map('trim', (array) ($group['items'] ?? []))));
            if ($label === '' || empty($items)) {
                continue;
            }

            $html .= '<div class="skill-group">';
            $html .= '<div class="skill-group-label">' . esc($label) . '</div>';
            $html .= '<div class="skill-group-items">' . esc(implode(' | ', $items)) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div></section>';

        return $html;
    }

    private function renderTextSection(string $title, string $body): string
    {
        if (trim($body) === '') {
            return '';
        }

        return '<section class="resume-section"><h3>' . esc($title) . '</h3><div class="resume-text">'
            . nl2br(esc($body))
            . '</div></section>';
    }

    private function renderSkillChips(array $skills): string
    {
        $html = '';
        foreach (array_values(array_filter(array_map('trim', $skills))) as $skill) {
            $html .= '<span class="resume-chip">' . esc($skill) . '</span>';
        }

        return $html;
    }

    private function documentStyles(): string
    {
        return '
            @page{margin:18mm 16mm}
            body{margin:0;padding:26px;background:#edf2f7;font-family:Segoe UI,Arial,sans-serif;color:#0f172a}
            .resume-template-shell{background:#fff;border-radius:28px;padding:40px 38px;box-shadow:0 24px 70px rgba(15,23,42,.1);max-width:980px;margin:0 auto}
            .template-modern{background:linear-gradient(180deg,#ffffff 0%,#f9fbff 100%)}
            .resume-hero{display:flex;justify-content:space-between;gap:28px;align-items:flex-start;margin-bottom:26px;padding-bottom:22px;border-bottom:1px solid #dbe7f3}
            .resume-name{font-size:2.35rem;font-weight:800;line-height:1.05;color:#0f172a;letter-spacing:-.03em}
            .resume-role{font-size:1rem;font-weight:700;color:#0f766e;margin-top:9px;text-transform:uppercase;letter-spacing:.1em}
            .resume-summary{font-size:1rem;line-height:1.82;color:#334155;margin-bottom:24px;max-width:72ch}
            .resume-section{margin-top:28px}
            .resume-section h3{font-size:.82rem;letter-spacing:.18em;text-transform:uppercase;color:#64748b;margin-bottom:16px}
            .resume-item{padding:18px 0;border-top:1px solid #eef2f7}
            .resume-item:first-child{border-top:0;padding-top:0}
            .resume-item-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
            .resume-item-title{font-size:1.08rem;font-weight:800;color:#111827}
            .resume-item-subhead{font-size:.96rem;font-weight:700;color:#2563eb}
            .resume-item-meta{font-size:.85rem;color:#64748b;margin-top:6px}
            .resume-item ul{margin:12px 0 0 18px;padding:0}
            .resume-item li{margin:0 0 8px;color:#334155;line-height:1.7}
            .resume-chip-row{display:flex;flex-wrap:wrap;gap:8px}
            .resume-chip{display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:.8rem;font-weight:800}
            .skill-groups{display:grid;gap:14px}
            .skill-group{display:grid;grid-template-columns:176px 1fr;gap:18px;padding:12px 0;border-top:1px solid #eef2f7}
            .skill-group:first-child{border-top:0;padding-top:0}
            .skill-group-label{font-size:.82rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#0f766e}
            .skill-group-items{font-size:.97rem;color:#334155;line-height:1.75}
            .template-sidebar{display:grid;grid-template-columns:300px 1fr;gap:28px;background:#fff}
            .resume-sidebar{background:linear-gradient(180deg,#0f172a 0%,#172554 100%);color:#fff;border-radius:22px;padding:28px}
            .resume-sidebar .resume-name,.resume-sidebar .resume-role,.resume-sidebar .resume-section h3,.resume-sidebar .resume-summary,.resume-sidebar .compact-list div{color:#fff}
            .resume-sidebar .resume-chip{background:rgba(255,255,255,.12);color:#fff}
            .resume-sidebar .skill-group{border-top-color:rgba(255,255,255,.16)}
            .resume-sidebar .skill-group-label,.resume-sidebar .skill-group-items{color:#fff}
            .resume-main{padding-top:4px}
            .compact-section .compact-list div{margin-bottom:8px;color:#334155;line-height:1.5}
            .resume-text{line-height:1.7;color:#334155}
            .template-tech{background:#f8fafc}
            .tech-header{border-left:4px solid #059669;padding-left:22px;margin-bottom:22px}
            .template-tech .resume-name{font-size:2.1rem;color:#111827}
            .template-tech .resume-role{color:#059669;font-size:.95rem}
            .template-tech .resume-section{margin-top:20px}
            .template-tech .resume-section h3{font-size:.78rem;color:#059669;background:#ecfdf5;padding:9px 14px;border-radius:6px}
            .template-tech .resume-item{padding:14px 0}
            @media print{
                body{padding:0;background:#fff}
                .resume-template-shell{max-width:none;margin:0;border-radius:0;box-shadow:none;padding:0}
            }
        ';
    }

    private function normalizeTemplateKey(string $templateKey): string
    {
        $templateKey = trim($templateKey);
        if ($templateKey === '') {
            return 'modern_professional';
        }

        $aliases = [
            'minimal_timeline' => 'modern_professional',
            'creative_bold' => 'modern_professional',
            'elegant_classic' => 'executive_sidebar',
        ];

        if (isset($aliases[$templateKey])) {
            return $aliases[$templateKey];
        }

        return array_key_exists($templateKey, $this->getTemplates()) ? $templateKey : 'modern_professional';
    }

    private function sanitizeFilename(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        $value = trim((string) $value, '-');

        return $value !== '' ? $value : 'resume';
    }
}
