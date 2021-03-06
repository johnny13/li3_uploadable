<?php
/**
 * li3_uploadable: Upload files via $_POST
 *
 * @copyright     Copyright 2013, Housni Yakoob (http://koobi.co)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\util\Validator;
use lithium\core\ConfigException;

/**
 * Checks to see if a file has been uploaded.
 *
 * In your model:
 * {{{
 * public $validates = [
 *     'avatar' => [
 *         [
 *             'isUploadedFile',
 *             'message' => 'You must upload a file.',
 *         ]
 *     ]
 * ];
 * }}}
 *
 * If the field is set to `null`, this means the user intends to delete it so it would return `true`.
 */
Validator::add('isUploadedFile', function($value, $rule, $options) {
	$defaults = [
		'skipEmpty' => false,
		'required' => true,
		'validateInCli' => false
	];
	$options += $defaults;

	if ($options['skipEmpty'] === true || $options['required'] === false) {
		return true;
	}

	if (!$options['validateInCli'] && PHP_SAPI === 'cli') {
		return true;
	}

	if (!isset($_FILES[$options['field']]['error']) && null === $_FILES[$options['field']]) {
		return true;
	}

	if ($_FILES[$options['field']]['error'] !== UPLOAD_ERR_NO_FILE) {
		return true;
	}
	return false;
});

/**
 * Checks to see if the uploaded file is within a file size range.
 *
 * In your model:
 * {{{
 * public $validates = [
 *     'avatar' => [
 *         [
 *	            'uploadedFileSize',
 *	            'message' => 'The image must be less than 2mb.',
 *				'in' => [0, 2, 'mb']
 *         ]
 *     ]
 * ];
 * }}}
 *
 * If the field is set to `null`, this means the user intends to delete it so it would return `true`.
 */
Validator::add('uploadedFileSize', function($value, $rule, $options) {
	$defaults = [
		'skipEmpty' => false,
		'required' => true,
		'validateInCli' => false,
		'in' => []
	];
	$options += $defaults;

	if ($options['skipEmpty'] === true || $options['required'] === false) {
		return true;
	}

	if (!$options['validateInCli'] && PHP_SAPI === 'cli') {
		return true;
	}

	$suffixes = [
		'' => 0, 'bytes' => 0, 'b' => 0,
		'kb' => 1, 'kilobytes' => 1,
		'mb' => 2, 'megabytes' => 2,
		'gb' => 3, 'gigabytes' => 3,
		'tb' => 4, 'terabyte' => 4,
		'pb' => 5, 'petabyte' => 5
	];
	$in = $options['in'];
	$unit = strtolower(array_pop($in));

	if (count($in) != 2) {
		throw new ConfigException('You must specify an upper and lower bound for `in`.');
	}

	if (!Validator::isInList($unit, null, ['list' => $suffixes])) {
		throw new ConfigException("Invalid unit `{$unit}` for size.");
	}

	if (!isset($_FILES[$options['field']]['error']) && null === $_FILES[$options['field']]) {
		return true;
	}
	$uploaded = $_FILES[$options['field']];

	list($lowerBound, $upperBound) = $in;
	$lower = round($lowerBound * pow(1024, $suffixes[$unit]));
	$upper = round($upperBound * pow(1024, $suffixes[$unit]));

	return Validator::isInRange($uploaded['size'], null, compact(
		'lower',
		'upper'
	));
});

/**
 * Checks to see if the uploaded file is of an allowed file type.
 *
 * In your model:
 * {{{
 * public $validates = [
 *     'avatar' => [
 *         [
 *	            'allowedFileType',
 *	            'message' => 'Please upload a JPG, PNG or GIF image.',
 *				'allowed' => [
 *					'image/png',
 *					'image/x-png',
 *					'image/jpeg',
 *					'image/pjpeg'
 *				]
 *         ]
 *     ]
 * ];
 * }}}
 *
 * If the field is set to `null`, this means the user intends to delete it so it would return `true`.
 */
Validator::add('allowedFileType', function($value, $rule, $options) {
	$defaults = [
		'skipEmpty' => false,
		'required' => true,
		'validateInCli' => false,
	];
	$options += $defaults;

	if ($options['skipEmpty'] === true || $options['required'] === false) {
		return true;
	}

	if (!$options['validateInCli'] && PHP_SAPI === 'cli') {
		return true;
	}

	if (!isset($_FILES[$options['field']]['error']) && null === $_FILES[$options['field']]) {
		return true;
	}

	$uploaded = $_FILES[$options['field']];
	return Validator::isInList($uploaded['type'], null, [
		'list' => $options['allowed']
	]);
});

/**
 * Checks to see if image dimensions are valid.
 * Both, width and height, are optional and will only be checked against if they are specified.
 *
 * In your model:
 * {{{
 * public $validates = [
 *     'avatar' => [
 *         [
 *		'dimensions',
 *		'width' => 45,
 *		'height' => 45,
 *		'message'   => 'The image dimensions must be 45 x 45'
 *         ]
 *     ]
 * ];
 * }}}
 *
 * If the field is set to `null`, this means the user intends to delete it so it would return `true`.
 */
Validator::add('dimensions', function($value, $rule, $options) {
	$status = [];
	$field = $options['field'];

	if ($options['required'] && empty($_FILES[$field]['tmp_name'])) {
		return false;
	}

	if ($options['skipEmpty'] && empty($_FILES[$field]['tmp_name'])) {
		return true;
	}

	if (!isset($_FILES[$options['field']]['error']) && null === $_FILES[$options['field']]) {
		return true;
	}

	list($width, $height, $type, $attr) = getimagesize($_FILES[$field]['tmp_name']);

	if (isset($options['width']) && $width !== $options['width']) {
		$status[] = false;
	}

	if (isset($options['height']) && $height !== $options['height']) {
		$status[] = false;
	}
	return !in_array(false, $status, true);
});
?>
