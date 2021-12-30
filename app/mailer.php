<?php

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

class Mailer
{
    protected $mailer;

    function __construct()
    {
        $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'ssl'))
            ->setUsername($_ENV['SMTP_USER'])
            ->setPassword($_ENV['SMTP_PASS']);

        $this->mailer = new Swift_Mailer($transport);
    }

    public function send(EmailData $data): bool
    {
        $logger = new \Swift_Plugins_Loggers_ArrayLogger();

        $message = (new Swift_Message($data->subject))
            ->setFrom([$data->emailFrom => $data->nameFrom])
            ->setTo([$data->emailTo => $data->nameTo])
            ->setBody($data->message);

        $result = $this->mailer->send($message);
        if (is_int($result) && $result > 0)
            return true;

        return false;
    }
}

class EmailData
{
    public String $emailFrom;
    public String $nameFrom;
    public String $message;
    public ?String $subject;
    public ?String $emailTo;
    public ?String $nameTo;

    function __construct(
        String $emailFrom,
        String $nameFrom,
        String $message,
        String $subject = null,
        String $emailTo = null,
        String $nameTo = null
    ) {
        $this->emailFrom = strtolower($emailFrom);
        $this->nameFrom = ucwords($nameFrom);
        $this->message = $message;

        if ($subject) {
            $this->subject = $subject;
        } else {
            $this->subject = "Pesan baru dari vCard";
        }

        if ($emailTo && filter_var(trim($emailTo), FILTER_VALIDATE_EMAIL)) {
            $this->emailTo = strtolower($emailTo);
        } else {
            $this->emailTo = strtolower($_ENV['DEST_EMAIL']);
        }

        if ($nameTo) {
            $this->nameTo = ucwords($nameTo);
        } else {
            $this->nameTo = ucwords($_ENV['DEST_NAME']);
        }

        $this->emailFrom = trim($this->emailFrom);
        $this->nameFrom = trim($this->nameFrom);
        $this->message = trim($this->message);
        $this->subject = trim($this->subject);
        $this->emailTo = trim($this->emailTo);
        $this->nameTo = trim($this->nameTo);
    }
}
