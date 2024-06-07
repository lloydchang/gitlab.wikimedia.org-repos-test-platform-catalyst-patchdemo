<?php

$wgQuickSurveysConfig = [
	[
		'name' => 'internal example survey',
		'type' => 'internal',
		'questions' => [
			[
				'name' => 'q1',
				'question' => 'ext-quicksurveys-example-internal-survey-question',
				// The respondent can choose one answer from a list.
				'layout' => 'single-answer',
				// The message key of the description of the survey. Displayed immediately below the survey question.
				//'description' => 'ext-quicksurveys-example-internal-survey-description',
				// Possible answer message keys for positive, neutral, and negative
				'answers' => [
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-positive',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-neutral',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-negative',
					],
				]
			]
		],
		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
	[
		'name' => 'internal example survey with description and freeform text',
		'type' => 'internal',
		'questions' => [
			[
				'name' => 'q1',
				'question' => 'ext-quicksurveys-example-internal-survey-question',
				// The respondent can choose one answer from a list.
				'layout' => 'single-answer',
				'description' => 'ext-quicksurveys-example-internal-survey-description',
				// The message key of the description of the survey. Displayed immediately below the survey question.
				//'description' => 'ext-quicksurveys-example-internal-survey-description',
				// Possible answer message keys for positive, neutral, and negative
				'answers' => [
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-positive',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-neutral',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-negative',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
				]
			]
		],
		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
	[
		'name' => 'internal multiple answer example survey',
		'type' => 'internal',
		'questions' => [
			[
				'name' => 'q1',
				'question' => 'ext-quicksurveys-example-internal-survey-question',
				// The respondent can choose one answer from a list.
				'layout' => 'multiple-answer',
				'description' => 'ext-quicksurveys-example-internal-survey-description',
				// The message key of the description of the survey. Displayed immediately below the survey question.
				//'description' => 'ext-quicksurveys-example-internal-survey-description',
				// Possible answer message keys for positive, neutral, and negative
				'answers' => [
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-positive',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-neutral',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-negative',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
				]
			]
		],

		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
	[
		'name' => 'internal multiple answer example survey with description and freeform text',
		'type' => 'internal',
		'questions' => [
			[
				'name' => 'q1',
				'question' => 'ext-quicksurveys-example-internal-survey-question',
				// The respondent can choose one answer from a list.
				'layout' => 'multiple-answer',
				'description' => 'ext-quicksurveys-example-internal-survey-description',
				'shuffleAnswersDisplay' => true,
				// The message key of the description of the survey. Displayed immediately below the survey question.
				//'description' => 'ext-quicksurveys-example-internal-survey-description',
				// Possible answer message keys for positive, neutral, and negative
				'answers' => [
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-positive',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-neutral',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
					[
						'label' => 'ext-quicksurveys-example-internal-survey-answer-negative',
						'freeformTextLabel' => 'ext-quicksurveys-example-internal-survey-freeform-text-label',
					],
				]
			]
		],
		'enabled' => true,
		'coverage' => 0,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
	[
		'name' => 'external example survey',
		'type' => 'external',
		'questions' => [
			[
				'question' => 'ext-quicksurveys-example-external-survey-question',
				'description' => 'ext-quicksurveys-example-external-survey-description',
				'link' => 'ext-quicksurveys-example-external-survey-link',
			]
		],
		'privacyPolicy' => 'ext-quicksurveys-example-external-survey-privacy-policy',
		'coverage' => 0,
		'enabled' => true,
		'platforms' => [
			'desktop' => [ 'stable' ],
			'mobile' => [ 'stable', 'beta' ],
		],
	],
];
