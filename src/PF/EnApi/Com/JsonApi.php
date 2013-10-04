<?php
namespace PF\EnApi\Com;

class JsonApi {
	/**
	 * Customer number
	 * @var Integer
	 */
	private $customerNo = null;

	/**
	 * User pin code
	 * @var integer
	 */
	private $pin = null;

	/**
	 * Resolution can be any of 1, 2, 3, 4 that maps to hours, days, months and years
	 *
	 * @var integer
	 */
	private $resolution = 2;

	/**
	 * Holds the response from the getToken request
	 * @var string
	 */
	private $getUserTokenResponse = null;

	/**
	 * Holds the response from the HentBruger request
	 * @var string
	 */
	private $getCustomerResponse = null;

	public function __construct($customerNo, $pin)
	{
		$this->customerNo = $customerNo;
		$this->pin = $pin;
	}

	public function login()
	{
		$url = sprintf(
			'https://mobiltoken.energinord.dk:7111/usertokenservice.svc/getToken?kundeNummer=%s&internetKode=%s&terms=true&callback=Ext.data.JsonP.callback5&_dc=%s',
			$this->customerNo,
			$this->pin,
			$this->getDc()
		);

		return $this->doRequest($url);
	}

	public function getCustomer()
	{
		$url = sprintf(
			'https://mobilafregning.energinord.dk:7112/afregningsservice.svc/HentKunde?userToken=%s&callback=Ext.data.JsonP.callback6&_dc=%s',
			$this->getUserToken(),
			$this->getDc()
		);

		return $this->doRequest($url);
	}

	public function getConsumption()
	{
		$url = sprintf(
			'https://mobilafregning.energinord.dk:7112/afregningsservice.svc/HentForbrug?userToken=%s&forbrugerNummer=%d&installationsNummer=%d&lokalNummer=%s&startDate=%s&slutDato=%s&oploesning=%d&callback=Ext.data.JsonP.callback7&_dc=%s',
			$this->getUserToken(),
			$this->getConsumerNo(),
			$this->getInstallationNo(),
			$this->getLocalNo(),
			$this->getStartDate(),
			$this->getEndDate(),
			$this->getResolution(),
			$this->getDc()
		);

		return $this->doRequest($url);
	}

	public function setResolution($resolution)
	{
		$this->resolution = $resolution;
	}

	public function setStartDate($date)
	{
		$this->startDate = $date;
	}

	public function setEndDate($date)
	{
		$this->endDate = $date;
	}

	private function getResolution()
	{
		return $this->resolution;
	}

	private function getStartDate()
	{
		/**
		 * If a start date has been set
		 * use that regardless of the resolution
		 */
		if($this->startDate) {
			return $this->startDate;
		}

		/**
		 * Pick a sane default start date
		 * based on the resolution
		 */
		switch($this->getResolution()) {
			case 1:
				/**
				 * Hours: Hours today since midnight
				 */
				return date('Y-m-d\T00:00:00');
				break;
			case 2:
				/**
				 * Days: Days within this month
				 */
				return date('Y-m-01\T00:00:00');
				break;
			case 3:
				/**
				 * Months: Months within this year
				 */
				return date('Y-01-01\T00:00:00');
				break;
			case 4:
				/**
				 * Years: Last five years
				 */
				return date('Y-01-01\T00:00:00', strtotime('-5 years'));
				break;
			default:
				/**
				 * Haters gonna hate
				 */
				return '01-01-1970T00:00:00';
				break;
		}
	}

	private function getEndDate()
	{
		/**
		 * If an end date has been set
		 * use that regardless of the resolution
		 */
		if($this->endDate) {
			return $this->endDate;
		}

		/**
		 * Pick a sane default end date
		 * based on the resolution
		 */
		switch($this->getResolution()) {
			case 1:
				/**
				 * Hours: Hours today since midnight
				 */
				return date('Y-m-d\T00:00:00', strtotime('tomorrow'));
				break;
			case 2:
				/**
				 * Days: Days within this month
				 */
				return date('Y-m-01\T00:00:00', strtotime('next month'));
				break;
			case 3:
				/**
				 * Months: Months within this year
				 */
				return date('Y-01-01\T00:00:00', strtotime('next year'));
				break;
			case 4:
				/**
				 * Years: Up until next year
				 */
				return date('Y-01-01\T00:00:00', strtotime('next year'));
				break;
			default:
				/**
				 * Haters gonna hate
				 */
				return '01-01-1970T00:00:00';
				break;
		}
	}

	private function getConsumerNo()
	{
		if(!$this->getCustomerResponse) {
			$this->getCustomerResponse = $this->getCustomer();
		}

		return $this->getCustomerResponse->HentKundeResult->ReturnData->Installationer[0]->ForbrugerNummer;

	}

	private function getInstallationNo()
	{
		if(!$this->getCustomerResponse) {
			$this->getCustomerResponse = $this->getCustomer();
		}

		return $this->getCustomerResponse->HentKundeResult->ReturnData->Installationer[0]->InstallationsNummer;
	}

	private function getLocalNo()
	{
		if(!$this->getCustomerResponse) {
			$this->getCustomerResponse = $this->getCustomer();
		}

		return $this->getCustomerResponse->HentKundeResult->ReturnData->Installationer[0]->AktiveMålere[0]->LokalNr;
	}

	private function doRequest($url)
	{
		$response = file_get_contents($url);
		$response = $this->stripJsonpCallback($response);
		return json_decode($response);
	}

	private function getUserToken()
	{
		if(!$this->getUserTokenResponse) {
			$this->getUserTokenResponse = $this->login();
		}

		return $this->getUserTokenResponse->GetUserTokenResult->ReturnData->Ticket;
	}

	private function stripJsonpCallback($response)
	{
		$success = preg_match(
			'/Ext.data.JsonP.callback[0-9]\((.*)\);/',
			$response,
			$matches
		);

		if(!$success) {
			throw new \Exception('Unable to parse response');
		}

		return $matches[1];
	}

	private function getDc()
	{
		return substr(str_replace('.', '', microtime('true')), -1);
	}
}