<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth_bootstrap.php';

function afrisense_public_enquiry_customer_id(PDO $pdo, string $fullname, string $email, string $phone): int
{
    $matchesPhone = $phone !== 'Not provided';
    $statement = $pdo->prepare(
        'SELECT `id`
         FROM `customers`
         WHERE `email` = :email OR (:matches_phone = 1 AND `phone_number` = :phone)
         ORDER BY `id` ASC
         LIMIT 1'
    );
    $statement->execute([
        'email' => $email,
        'matches_phone' => $matchesPhone ? 1 : 0,
        'phone' => $phone,
    ]);
    $customerId = $statement->fetchColumn();

    if ($customerId !== false) {
        $update = $pdo->prepare(
            'UPDATE `customers`
             SET `fullname` = :fullname,
                 `email` = :email,
                 `phone_number` = :phone,
                 `updated_at` = NOW()
             WHERE `id` = :id'
        );
        $update->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'id' => (int) $customerId,
        ]);

        return (int) $customerId;
    }

    $insert = $pdo->prepare(
        'INSERT INTO `customers` (`fullname`, `email`, `phone_number`)
         VALUES (:fullname, :email, :phone)'
    );
    $insert->execute([
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone,
    ]);

    return (int) $pdo->lastInsertId();
}

function afrisense_submit_public_enquiry(array $request, string $defaultType = 'General Enquiry'): array
{
    $fullname = trim((string) ($request['full_name'] ?? $request['fullname'] ?? ''));
    $email = strtolower(trim((string) ($request['email'] ?? '')));
    $phone = preg_replace('/\s+/', '', trim((string) ($request['phone'] ?? '')));
    $subject = trim((string) ($request['subject'] ?? ''));
    $message = trim((string) ($request['message'] ?? ''));
    $type = trim((string) ($request['enquiry_type'] ?? $defaultType));

    if ($fullname === '' || strlen($fullname) < 2) {
        return ['success' => false, 'message' => 'Please enter your full name.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if ($phone === '') {
        $phone = 'Not provided';
    }

    if ($subject === '') {
        return ['success' => false, 'message' => 'Please choose a subject.'];
    }

    if (strlen($message) < 10) {
        return ['success' => false, 'message' => 'Please enter a message with at least 10 characters.'];
    }

    $subjectPrefix = ucwords(str_replace(['_', '-'], ' ', $type));
    $storedSubject = str_contains(strtolower($subject), strtolower($subjectPrefix))
        ? $subject
        : $subjectPrefix . ' - ' . $subject;

    try {
        $pdo = afrisense_pdo();
        $pdo->beginTransaction();
        $customerId = afrisense_public_enquiry_customer_id($pdo, $fullname, $email, $phone);
        $insert = $pdo->prepare(
            'INSERT INTO `enquiries` (`customer_id`, `subject`, `message`, `status`)
             VALUES (:customer_id, :subject, :message, :status)'
        );
        $insert->execute([
            'customer_id' => $customerId,
            'subject' => $storedSubject,
            'message' => $message,
            'status' => 'Pending',
        ]);
        $pdo->commit();

        return ['success' => true, 'message' => 'Your enquiry has been submitted. Our team will respond shortly.'];
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['success' => false, 'message' => 'Your enquiry could not be submitted. Please try again.'];
    }
}
