<?php

declare(strict_types=1);

/**
 * AI MCQ engine configuration. Set MCQ_AI_ENCRYPTION_KEY in environment (32+ bytes recommended).
 */
return [
    'encryption_key' => getenv('MCQ_AI_ENCRYPTION_KEY') ?: 'acharya-books-mcq-ai-change-me-in-production-32b',
    'storage_pdf' => dirname(__DIR__) . '/storage/mcq_pdfs',
    'max_upload_mb' => 80,
    'default_questions_per_page' => 3,
];
