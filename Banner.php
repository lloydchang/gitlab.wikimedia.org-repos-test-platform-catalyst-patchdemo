<?php
/**
 * Class for managing banner display with date range checking
 */
class Banner {
	/**
	 * @var array Banner configuration containing:
	 *   - startDate: string Start date in 'Y-m-d H:i:s' format
	 *   - endDate: string End date in 'Y-m-d H:i:s' format
	 *   - bannerUrl: string URL the banner should direct to
	 *   - bannerText: string Text to define the banner
	 *   - linkPreviewText: string Text for the link/button
	 */
	public static $banner = [
		// Edit the sample text below to reflect the desired contents and time constraints for your banner announcement.
		// 'startDate' => '2025-04-10 00:00:00',
		// 'endDate' => '2025-04-20 00:00:00',
		// 'bannerUrl' => 'https://hello.com/hi',
		// 'bannerText' => 'PatchDemo can now update existing environments!',
		// 'linkPreviewText' => 'See more',
	];

	/**
	 * Check if current date is within the banner's active date range
	 * @return bool True if banner should be shown, false otherwise
	 */
	public static function isBannerWithinDateRange(): bool {
		$today = new DateTime();
		try {
			$startDate = new DateTime( self::$banner[ 'startDate' ] );
			$endDate = new DateTime( self::$banner[ 'endDate' ] );
			return ( $today >= $startDate ) && ( $today <= $endDate );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get the banner configuration array
	 * @return array The banner configuration
	 */
	public static function getBannerData(): array {
		return self::$banner;
	}
}
