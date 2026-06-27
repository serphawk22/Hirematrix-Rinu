<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class LegalPages extends Controller
{
    public function about()
    {
        return view('site/about', [
            'pageTitle' => 'About HireMatrix',
        ]);
    }

    public function contact()
    {
        return view('site/contact', [
            'pageTitle' => 'Contact HireMatrix',
        ]);
    }

    public function submitContact()
    {
        $name = trim((string) $this->request->getPost('name'));
        $email = trim((string) $this->request->getPost('email'));
        $subject = trim((string) $this->request->getPost('subject'));
        $message = trim((string) $this->request->getPost('message'));

        $errors = [];
        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if ($subject === '') {
            $errors[] = 'Subject is required.';
        }
        if ($message === '') {
            $errors[] = 'Message is required.';
        }

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('contact_errors', $errors);
        }

        $stored = false;

        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('feedback')) {
                $insert = [
                    'user_id' => null,
                    'name' => $name,
                    'email' => $email,
                    'role' => 'public_contact',
                    'message' => "Subject: {$subject}\n\n{$message}",
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                if ($db->fieldExists('rating', 'feedback')) {
                    $insert['rating'] = null;
                }

                $db->table('feedback')->insert($insert);
                $stored = true;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Public contact submission failed to store: ' . $e->getMessage());
        }

        log_message(
            'info',
            'Public contact submission received from ' . $email
            . ' | subject=' . $subject
            . ' | stored=' . ($stored ? 'yes' : 'no')
        );

        return redirect()->to(base_url('contact'))->with(
            'contact_success',
            'Thanks, your message has been received. Our team will review it shortly.'
        );
    }

    public function privacyPolicy()
    {
        return view('legal/document', [
            'pageTitle' => 'Privacy Policy',
            'pageEyebrow' => 'Legal',
            'pageHeading' => 'Privacy Policy',
            'pageSummary' => 'How HireMatrix collects, uses, stores, and protects personal information across the job portal.',
            'effectiveDate' => 'June 16, 2026',
            'sections' => [
                [
                    'title' => '1. Information We Collect',
                    'paragraphs' => [
                        'We may collect information you provide directly, including your name, email address, phone number, resume details, work history, education, skills, profile photo, company information, and communication preferences.',
                        'We may also collect platform usage data such as login activity, device and browser information, IP address, pages visited, actions taken within the portal, and technical logs needed for security, analytics, and service improvement.',
                    ],
                ],
                [
                    'title' => '2. How We Use Your Information',
                    'paragraphs' => [
                        'We use personal information to create and manage accounts, support recruitment workflows, enable job applications, match candidates with opportunities, facilitate recruiter communication, process AI-assisted features, maintain platform security, and improve portal performance.',
                        'We may also use information to send service-related notifications, verification messages, interview updates, password resets, billing or subscription messages, and other operational communications.',
                    ],
                ],
                [
                    'title' => '3. How Information Is Shared',
                    'paragraphs' => [
                        'Candidate information may be shared with recruiters, employers, or authorized company users when a candidate applies for a role, is shortlisted, joins a hiring workflow, or otherwise chooses to engage with an opportunity on the platform.',
                        'We may share limited information with service providers who support hosting, analytics, communication delivery, interview tooling, payment processing, or system operations, subject to appropriate confidentiality and security controls.',
                        'We may also disclose information when required by law, to protect legal rights, to enforce portal policies, or to investigate fraud, abuse, or security incidents.',
                    ],
                ],
                [
                    'title' => '4. Cookies and Similar Technologies',
                    'paragraphs' => [
                        'HireMatrix may use cookies, local storage, session tools, and similar technologies to keep users signed in, remember preferences, improve performance, measure engagement, and support essential portal functionality.',
                    ],
                ],
                [
                    'title' => '5. Data Collection',
                    'paragraphs' => [
                        'We retain information for as long as reasonably necessary to operate the job portal, maintain hiring records, comply with legal obligations, resolve disputes, enforce agreements, and support legitimate business needs.',
                    ],
                ],
                [
                    'title' => '6. Security',
                    'paragraphs' => [
                        'We apply reasonable administrative, technical, and organizational measures designed to protect personal information. However, no online platform or storage system can be guaranteed to be completely secure.',
                    ],
                ],
                [
                    'title' => '7. Your Choices and Rights',
                    'paragraphs' => [
                        'Depending on applicable law, users may have rights to access, correct, update, delete, or restrict certain personal information. Users may also update profile details and account preferences within the portal where those features are available.',
                    ],
                ],
                [
                    'title' => '8. Third-Party Services and Links',
                    'paragraphs' => [
                        'The portal may link to third-party websites, external job feeds, or integrated services. We are not responsible for the privacy practices or content of third-party properties that are not controlled by HireMatrix.',
                    ],
                ],
                [
                    'title' => '9. Children’s Privacy',
                    'paragraphs' => [
                        'HireMatrix is intended for professional and career-related use and is not directed to children. Users should only use the platform if they are legally permitted to do so under applicable law.',
                    ],
                ],
                [
                    'title' => '10. Policy Updates',
                    'paragraphs' => [
                        'We may update this Privacy Policy from time to time to reflect operational, legal, or product changes. Continued use of the portal after an update takes effect means the revised policy will apply going forward.',
                    ],
                ],
                [
                    'title' => '11. Contact',
                    'paragraphs' => [
                        'Questions about this Privacy Policy can be directed to the HireMatrix team through the support or contact channel published on the portal.',
                    ],
                ],
                [
                    'title' => '12. Google Ads',
            'paragraphs' => [
    'This website uses Google AdSense, a web advertising service provided by Google LLC. Google AdSense uses cookies to serve ads based on your prior visits to this website or other websites. You may opt out of personalized advertising by visiting Google\'s Ads Settings.'
],
                    ],
            ],
        ]);
    }

    public function termsOfService()
    {
        return view('legal/document', [
            'pageTitle' => 'Terms of Service',
            'pageEyebrow' => 'Legal',
            'pageHeading' => 'Terms of Service',
            'pageSummary' => 'The rules, responsibilities, and acceptable use standards for candidates, recruiters, and employers using HireMatrix.',
            'effectiveDate' => 'June 16, 2026',
            'sections' => [
                [
                    'title' => '1. Acceptance of Terms',
                    'paragraphs' => [
                        'By accessing or using HireMatrix, you agree to be bound by these Terms of Service and any policies incorporated by reference. If you do not agree, you should not use the portal.',
                    ],
                ],
                [
                    'title' => '2. Eligibility and Accounts',
                    'paragraphs' => [
                        'You are responsible for ensuring that the information you submit is accurate, current, and complete. You are also responsible for maintaining the confidentiality of your account credentials and for activity carried out through your account.',
                    ],
                ],
                [
                    'title' => '3. Candidate Use of the Portal',
                    'paragraphs' => [
                        'Candidates may use the platform to create profiles, upload resumes, receive job suggestions, apply to roles, and participate in assessments or interviews where enabled.',
                        'Candidates must provide truthful professional information and must not impersonate another person, misrepresent qualifications, or attempt to manipulate rankings, assessments, or interview outcomes.',
                    ],
                ],
                [
                    'title' => '4. Recruiter and Employer Use',
                    'paragraphs' => [
                        'Recruiters and employers are responsible for posting lawful job opportunities, handling candidate data appropriately, and using the platform in a fair, professional, and non-discriminatory manner.',
                        'Job listings, outreach, and hiring actions must comply with applicable employment laws, privacy obligations, and internal company policies.',
                    ],
                ],
                [
                    'title' => '5. Acceptable Use',
                    'paragraphs' => [
                        'You may not use HireMatrix to upload unlawful, infringing, abusive, misleading, fraudulent, defamatory, or malicious content, or to interfere with platform security, availability, or normal operation.',
                        'You may not attempt unauthorized access, data extraction beyond permitted use, automated abuse, reverse engineering where prohibited, or any activity that harms users, companies, or the platform.',
                    ],
                ],
                [
                    'title' => '6. AI-Assisted Features',
                    'paragraphs' => [
                        'HireMatrix may provide AI-assisted functionality such as resume analysis, job matching, interview generation, or candidate screening support. These features are intended to assist workflows and should not be treated as guaranteed outcomes, legal advice, or professional certification.',
                    ],
                ],
                [
                    'title' => '7. External Jobs and Third-Party Content',
                    'paragraphs' => [
                        'The portal may display external job listings, third-party links, or integrated services. HireMatrix does not guarantee the accuracy, availability, legitimacy, or continued existence of third-party opportunities or external content.',
                    ],
                ],
                [
                    'title' => '8. Intellectual Property',
                    'paragraphs' => [
                        'The platform, including its software, branding, interface, and related materials, remains the property of HireMatrix or its licensors. Users retain ownership of content they submit, but grant the platform the rights reasonably necessary to host, process, display, and operate that content within the service.',
                    ],
                ],
                [
                    'title' => '9. Suspension and Termination',
                    'paragraphs' => [
                        'We may suspend, restrict, or terminate access where necessary to protect the platform, enforce these terms, respond to abuse, investigate suspicious activity, or comply with legal obligations.',
                    ],
                ],
                [
                    'title' => '10. Disclaimers',
                    'paragraphs' => [
                        'HireMatrix is provided on an as-available basis. To the maximum extent permitted by law, we do not guarantee uninterrupted access, error-free operation, hiring success, candidate placement, recruiter response rates, or specific business outcomes.',
                    ],
                ],
                [
                    'title' => '11. Limitation of Liability',
                    'paragraphs' => [
                        'To the extent permitted by law, HireMatrix and its operators will not be liable for indirect, incidental, special, consequential, or punitive damages arising out of or related to the use of the portal, third-party content, hiring decisions, or service interruptions.',
                    ],
                ],
                [
                    'title' => '12. Changes to These Terms',
                    'paragraphs' => [
                        'We may revise these Terms of Service from time to time. Updated terms become effective when posted unless a later effective date is stated. Continued use of the portal after updates means the revised terms apply.',
                    ],
                ],
                [
                    'title' => '13. Contact',
                    'paragraphs' => [
                        'Questions about these Terms of Service can be directed to the HireMatrix team through the support or contact channel published on the portal.',
                    ],
                ],
            ],
        ]);
    }
}
