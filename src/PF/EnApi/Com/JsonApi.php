<?php
namespace PF\EnApi\Com;

class JsonApi {
	private $customerNo = null;
	private $pin = null;
	private $resolution = 2; // Default to day
	private $getUserTokenResponse = null;
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
			/**
			 * Dates seems to depend upon the resolution
			 * Resolution 1-3 requires the entire year
			 * while 4 (yearly) requires years and months.
			 */
			'2013-01-01T00%3A00%3A00', # start date
			'2013-12-31T00%3A00%3A00', # end date
			$this->getResolution(), # Resolution 1: hours, 2: days, 3: months, 4: years
			$this->getDc()
		);

		return $this->doRequest($url);
	}

	public function setResolution($resolution)
	{
		$this->resolution = $resolution;
	}

	public function getResolution()
	{
		return $this->resolution;
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