<?php
/**
 * mailDKIM : All images in eMail (src/background) will be downloaded and converted to base64.
 * 
 *
 * @author DEric Wagener
 * @copyright 2020 Eric Wagener <http://www.xites.nl>
 * @license MIT
 * @version 1.0.0
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * The MIT License
 */
class mailDKIM extends PluginBase {
	protected $storage = 'DbStorage';
    static protected $description = "Add DKIM signature to eMail.";
    static protected $name = "mailDKIM";
	private $imageReplacements = [];
	private $pluginsettings = false;
	
    protected $settings = [
        "information" => [
            "type" => "info",
            "content" => "In some cases SMTP mail is needed.",
        ],
        "record" => [
            "type" => "info",
            "content" => "",
        ],
        "dkimDomain" => [
            "type" => "string",
            "label"=>"Domain",
            "help"=>"example.com",
            "default"=>"",
        ],
        "dkimSelector" => [
            "type" => "string",
            "label"=>"Selector",
            "help"=>"First part of DNS txt record: selector._domainkey.example.com",
            "default"=>"",
        ],
        "dkimPassphrase" => [
            "type" => "password",
            "label"=>"Passphrase",
            "help"=>"Optional",
            "default"=>"",
        ],
        "dkimPrivate" => [
            "type" => "text",
            "label"=>"Private key",
            "help"=>"PEM Structure like: -----BEGIN PRIVATE KEY-----",
            "default"=>"",
        ],
	];

    
    public function init() {
		$domain		= $this->getSetting("dkimDomain");
		$selector	= $this->getSetting("dkimSelector");
		$txt = "";
		if ($domain and $selector) {
			$url = $selector."._domainkey.".$domain;
			$record = dns_get_record($selector."._domainkey.".$domain, DNS_TXT);
			if (!count($record) or !isset($record[0]["txt"])) {
				$txt = "Record not found: ".$url;
			} else {
				$txt = "DNS Record OK: ".$url;
			}
			//Yii::log(var_export($record, true), "info","application.plugins.mailDKIM");
		}
		$this->settings["record"]["content"] = $txt;
		
        $this->subscribe("beforeEmail","beforeEmail");
        $this->subscribe("beforeSurveyEmail","beforeEmail");
        $this->subscribe("beforeTokenEmail","beforeEmail");
    }

    /**
     * Set From and Bounce of PHPmailer to siteadminemail
     * @link https://manual.limesurvey.org/BeforeTokenEmail
     */
    public function beforeEmail() {
        $limeMailer = $this->getEvent()->get("mailer");
		//Yii::log(var_export($pluginsettings ?? false, true), "info","application.plugins.mailDKIM");
		$limeMailer->DKIM_domain = $this->getSetting("dkimDomain");
		$limeMailer->DKIM_private_string = $this->getSetting("dkimPrivate");
		$limeMailer->DKIM_selector = $this->getSetting("dkimSelector");
		$limeMailer->DKIM_passphrase = $this->getSetting("dkimPassphrase");
		$limeMailer->DKIM_copyHeaderFields = false;
		$limeMailer->CharSet = "utf-8";
		$limeMailer->Encoding = "quoted-printable";
		$oSurvey = Survey::model()->findByPk($limeMailer->surveyId);
		if ($oSurvey->oOptions->adminemail) {
			$limeMailer->DKIM_identity = $oSurvey->oOptions->adminemail;
		} else {
			$limeMailer->DKIM_identity = Yii::app()->getConfig("siteadminemail");
		}
		if (stripos($limeMailer->DKIM_identity, $limeMailer->DKIM_domain) === false) {
			$limeMailer->DKIM_domain = "";
		}
    }
	
    /**
     * @param string $setting
     * @return string
     */
    private function getSetting($setting, $key = "current") {
		if (!$this->pluginsettings) {
			$this->pluginsettings = $this->getPluginSettings(true);
		}
		return $this->pluginsettings[$setting][$key] ?? "";
    }

	
}
