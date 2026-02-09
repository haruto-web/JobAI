<?php

namespace App\Services;

use OpenAI;

class OpenAIService
{
    protected $client;

    public function getClient()
    {
        return $this->client;
    }

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');

        if (!is_string($apiKey) || trim($apiKey) === '') {
            // Throw a clear exception so callers can handle missing API key
            throw new \RuntimeException('OpenAI API key not configured in services.openai.api_key');
        }

        $this->client = OpenAI::client($apiKey);
    }

    public function extractSkillsFromResume($resumeText)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI assistant that extracts skills from resume text. Return only a JSON array of skills.'
                ],
                [
                    'role' => 'user',
                    'content' => "Extract skills from this resume text: {$resumeText}"
                ]
            ],
            'max_tokens' => 500,
        ]);

        $content = $response->choices[0]->message->content;
        $skills = json_decode($content, true);

        return is_array($skills) ? $skills : [];
    }

    public function analyzeResumeComprehensively($resumeText)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI assistant that analyzes resumes comprehensively. Extract detailed information and return a JSON object with the following structure:
                    {
                        "skills": ["skill1", "skill2"],
                        "experience_years": "X years",
                        "education": ["degree1", "degree2"],
                        "certifications": ["cert1", "cert2"],
                        "languages": ["language1", "language2"],
                        "summary": "Brief professional summary",
                        "strengths": ["strength1", "strength2"],
                        "experience_level": "entry|mid|senior|expert",
                        "key_achievements": ["achievement1", "achievement2"]
                    }'
                ],
                [
                    'role' => 'user',
                    'content' => "Analyze this resume comprehensively: {$resumeText}"
                ]
            ],
            'max_tokens' => 1000,
        ]);

        $content = $response->choices[0]->message->content;
        $analysis = json_decode($content, true);

        return $analysis ?: [
            'skills' => [],
            'experience_years' => 'Unknown',
            'education' => [],
            'certifications' => [],
            'languages' => [],
            'summary' => 'Unable to generate summary',
            'strengths' => [],
            'experience_level' => 'entry',
            'key_achievements' => []
        ];
    }

    public function matchJobToUser($jobDescription, $userSkills, $userExperience)
    {
        $skillsString = implode(', ', $userSkills);

        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI assistant that matches jobs to users based on skills and experience. Return only a JSON object with "match_score" (0-100) and "reasoning".'
                ],
                [
                    'role' => 'user',
                    'content' => "Job: {$jobDescription}\nUser Skills: {$skillsString}\nExperience: {$userExperience}\nCalculate match score."
                ]
            ],
            'max_tokens' => 300,
        ]);

        $content = $response->choices[0]->message->content;
        $result = json_decode($content, true);

        return $result ?: ['match_score' => 0, 'reasoning' => 'Unable to calculate match'];
    }

    /**
     * Suggest jobs given user's skills and experience.
     * Returns an array of suggestions: [{title, short_description, match_reason, score}]
     */
    public function suggestJobsForSkills(array $skills, string $experience = '', int $limit = 5)
    {
        $skillsString = implode(', ', $skills);

        $prompt = "You are an AI career advisor. Given the user's skills: {$skillsString} and experience: {$experience}, return up to {$limit} suggested job titles and short descriptions that best fit this user. Respond with a JSON array of objects with keys: title, description, recommended_level (entry|mid|senior|expert), and confidence (0-100).";

        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert career advisor.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 800,
        ]);

        $content = $response->choices[0]->message->content;
        $suggestions = json_decode($content, true);

        return is_array($suggestions) ? $suggestions : [];
    }

    /**
     * Generate a response enhanced with web search results
     */
    public function generateSearchEnhancedResponse($message, $searchResults, $userType = 'job_seeker')
    {
        $searchContext = '';
        if (!empty($searchResults)) {
            $searchContext = "Web search results:\n";
            foreach ($searchResults as $i => $result) {
                $searchContext .= ($i + 1) . ". " . $result['title'] . "\n";
                $searchContext .= "   " . $result['snippet'] . "\n";
                $searchContext .= "   Source: " . $result['link'] . "\n\n";
            }
        }

        $systemPrompt = $userType === 'employer'
            ? "You are an AI assistant for employers on a job recommendation website. Help employers with job posting creation, candidate management, hiring strategies, and company branding. Use the provided web search results to enhance your response when relevant. Be professional, helpful, and focused on employer needs."
            : "You are an AI career advisor chatbot for a job recommendation website. Help users with job search & matching, application help, company information, interview assistance, status updates, and career advice. Use the provided web search results to enhance your response when relevant. Be friendly, helpful, and professional.";

        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt . "\n\n" . $searchContext],
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 500,
        ]);

        return $response->choices[0]->message->content;
    }
}
