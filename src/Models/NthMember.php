<?php

namespace App\Models;

use App\DatabaseFactory;
use Exception;
use PDO;
use PDOException;

class NthMember
{
    private $_pdo = null;
    private $_nth_members_table = 'nth_members';
    private $_pages_table       = 'pages';
    private $_logs_table        = 'action_logs';

    private $_error_message = '';

    private $_success_status = 'success';
    private $_failure_status = 'failure';

    public function __construct()
    {
        try {
            $this->_pdo = DatabaseFactory::getConnection(__DIR__ . '/../assets/config/face.php');
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getErrorMessage(): string
    {
        return $this->_error_message;
    }

    public function processSubscribedNthMember(string $msisdn): void
    {
        // TODO: имплементирай логика за обработка на абониран член
        throw new \RuntimeException('processSubscribedNthMember() is not implemented yet.');
    }

    private function _prepare_PDO_ERROR(PDOException $e): void
    {
        // Поправка: дублираният ред е премахнат
        $error_code           = $e->getCode();
        $this->_error_message = $e->getMessage();

        switch ($error_code) {
            case 23000:
                $this->_error_message = 'Such Combination Already exist !';
                break;
        }
    }

    public function GetPrivacyPolicy(int $langID): mixed
    {
        $name = 'Nutzungs bedingungen';
        return $this->_get_page_by_name_and_country_id($name, $langID);
    }

    public function GetTermsAndConditionsPage(int $langID): mixed
    {
        $field_name = 'terms_and_conditions';
        return $this->_get_page_by_country_id_and_field_name($langID, $field_name);
    }

    public function GetContactUsPage(int $langID): mixed
    {
        $field_name = 'Contact';
        return $this->_get_page_by_country_id_and_field_name($langID, $field_name);
    }

    public function GetLegalPage(int $langID): mixed
    {
        $field_name = 'legal';
        return $this->_get_page_by_country_id_and_field_name($langID, $field_name);
    }

    public function GetAboutPage(int $langID): mixed
    {
        $field_name = 'about';
        return $this->_get_page_by_country_id_and_field_name($langID, $field_name);
    }

    public function GetSubscriptionManagementPage(int $langID): mixed
    {
        $field_name = 'subscription_management';
        return $this->_get_page_by_country_id_and_field_name($langID, $field_name);
    }

    public function GetTermsAndConditions(int $langID): mixed
    {
        $name = 'AGB';
        return $this->_get_page_by_name_and_country_id($name, $langID);
    }

    private function _get_page_by_name_and_country_id(string $name, int $country_id): mixed
    {
        $sql  = "SELECT * FROM $this->_pages_table WHERE country_id = :country_id AND name = :name";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute(['country_id' => $country_id, 'name' => $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function _get_page_by_country_id_and_field_name(int $country_id, string $field_name): mixed
    {
        $sql  = "SELECT * FROM $this->_pages_table WHERE country_id = :country_id AND field_name = :field_name";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute(['country_id' => $country_id, 'field_name' => $field_name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function w_create(string $table, array $data): mixed
    {
        $columns      = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql  = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->_pdo->prepare($sql);

        if ($stmt->execute($data)) {
            return $this->_pdo->lastInsertId();
        }
        return false;
    }

    public function GetNthMemberByMsisdn(string $msisdn): mixed
    {
        return $this->_get_nth_member_by_msisdn($msisdn);
    }

    private function _get_nth_member_by_msisdn(string $msisdn): mixed
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM $this->_nth_members_table WHERE msisdn=:msisdn");
        $stmt->bindValue(':msisdn', $msisdn);
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->_prepare_PDO_ERROR($e);
            return 'error';
        }
    }

    private function _get_nth_member_event_by_msisdn(string $msisdn): mixed
    {
        $stmt = $this->_pdo->prepare("SELECT event FROM $this->_nth_members_table WHERE msisdn=:msisdn");
        $stmt->bindValue(':msisdn', $msisdn);
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->_prepare_PDO_ERROR($e);
            return 'error';
        }
    }

    public function redirectNotmember(string $page, string $message = 'Sorry ! Cannot find your phone number !'): void
    {
        $_SESSION['redirect_reason'] = $message;
        $this->redirect_to_page($page);
    }

    private function redirect_to_page(string $page): void
    {
        if (ob_get_length() === false) {
            ob_start();
        }
        header("Location: $page");
        exit;
    }

    public function getNthMemberEventByMsisdn(string $msisdn): mixed
    {
        $event = $this->_get_nth_member_event_by_msisdn($msisdn);
        return $event ?: false;
    }

    private function _get_nth_member_event_by_public_uuid(string $public_uuid): mixed
    {
        $stmt = $this->_pdo->prepare("SELECT event FROM $this->_nth_members_table WHERE public_uuid=:public_uuid");
        $stmt->bindValue(':public_uuid', $public_uuid);
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->_prepare_PDO_ERROR($e);
            $this->logAction('get nth member event', $this->_failure_status, $this->_error_message);
            return 'error';
        }
    }

    private function _get_nth_member_by_public_uuid(string $public_uuid): mixed
    {
        $stmt = $this->_pdo->prepare("SELECT * FROM $this->_nth_members_table WHERE public_uuid=:public_uuid");
        $stmt->bindValue(':public_uuid', $public_uuid);
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->_prepare_PDO_ERROR($e);
            $this->logAction('get nth member event', $this->_failure_status, $this->_error_message);
            return 'error';
        }
    }

    private function _get_nth_member_msisdn_by_public_uuid(string $public_uuid): mixed
    {
        $stmt = $this->_pdo->prepare("SELECT msisdn FROM $this->_nth_members_table WHERE public_uuid=:public_uuid");
        $stmt->bindValue(':public_uuid', $public_uuid);
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->_prepare_PDO_ERROR($e);
            return 'error';
        }
    }

    public function getNthMemberMsisdnByPublicUuid(string $public_uuid): mixed
    {
        return $this->_get_nth_member_msisdn_by_public_uuid($public_uuid);
    }

    public function getNthMemberByPublicUuid(string $public_uuid): mixed
    {
        return $this->_get_nth_member_by_public_uuid($public_uuid);
    }

    public function getNthMemberEventByPublicUuid(string $public_uuid): mixed
    {
        return $this->_get_nth_member_event_by_public_uuid($public_uuid);
    }

    private function logAction(string $action, string $status, ?string $message = null): void
    {
        try {
            $sql  = "INSERT INTO $this->_logs_table (action, status, message, created_at)
                     VALUES (:action, :status, :message, :created_at)";
            $stmt = $this->_pdo->prepare($sql);
            $stmt->execute([
                ':action'     => $action,
                ':status'     => $status,
                ':message'    => $message,
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            // ignore logging failure
        }
    }
}