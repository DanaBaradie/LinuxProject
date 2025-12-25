# SendGrid Integration Setup Guide

This guide will help you set up SendGrid for sending and receiving emails in the School Bus Tracking System.

## Prerequisites

1. SendGrid account (sign up at https://sendgrid.com/)
2. Composer installed
3. PHP 8.0 or higher

## Step 1: Install SendGrid Package

Run the following command in your project root:

```bash
composer install
```

This will install the SendGrid PHP library along with other dependencies.

## Step 2: Get SendGrid API Key

1. Log in to your SendGrid account
2. Go to **Settings** → **API Keys**
3. Click **Create API Key**
4. Give it a name (e.g., "Bus Tracking System")
5. Select **Full Access** or **Restricted Access** (with Mail Send permissions)
6. Copy the API key (you won't be able to see it again!)

## Step 3: Configure SendGrid in Your Project

Edit `config/config.php` and update the SendGrid settings:

```php
// SendGrid Configuration
define('SENDGRID_API_KEY', 'SG.your_actual_api_key_here');
define('SENDGRID_FROM_EMAIL', 'noreply@yourdomain.com');
define('SENDGRID_FROM_NAME', 'School Bus Tracking System');
define('SENDGRID_REPLY_TO', 'support@yourdomain.com');
```

**Important:** 
- Replace `yourdomain.com` with your actual domain
- The `SENDGRID_FROM_EMAIL` must be verified in SendGrid
- For testing, you can use SendGrid's sandbox mode

## Step 4: Verify Your Sender Identity

### Option A: Single Sender Verification (For Testing)

1. Go to **Settings** → **Sender Authentication** → **Single Sender Verification**
2. Click **Create New Sender**
3. Fill in your details:
   - **From Email Address**: The email you want to send from
   - **From Name**: Your name or company name
   - **Reply To**: Where replies should go
4. Verify the email address (check your inbox)
5. Use this email in `SENDGRID_FROM_EMAIL`

### Option B: Domain Authentication (For Production)

1. Go to **Settings** → **Sender Authentication** → **Domain Authentication**
2. Click **Authenticate Your Domain**
3. Follow the DNS setup instructions
4. Once verified, you can send from any email @yourdomain.com

## Step 5: Set Up Inbound Email (Receiving Emails)

### 5.1 Configure Inbound Parse Webhook

1. Go to **Settings** → **Inbound Parse**
2. Click **Add Host & URL**
3. Configure:
   - **Subdomain**: mail (or any subdomain you prefer)
   - **Domain**: yourdomain.com
   - **Destination URL**: `https://yourdomain.com/api/email/sendgrid-webhook.php`
   - **Check "POST the raw, full MIME message"**
4. Click **Add**
5. Add the DNS records shown to your domain's DNS settings

### 5.2 Set Up Webhook for Email Events (Optional)

1. Go to **Settings** → **Mail Settings** → **Event Webhook**
2. Click **Create Webhook**
3. Set:
   - **HTTP POST URL**: `https://yourdomain.com/api/email/sendgrid-webhook.php`
   - **Events to Track**: Select events you want (bounce, delivered, open, click, etc.)
4. Save the webhook

## Step 6: Test Email Sending

You can test by:

1. **Via Admin Panel:**
   - Log in as admin
   - Go to Messages/Email section
   - Send a test email

2. **Via API:**
   ```bash
   curl -X POST https://yourdomain.com/api/email/send.php \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "to=test@example.com&subject=Test&message=Hello&type=custom"
   ```

## Step 7: Test Email Receiving

1. Send an email to: `mail@yourdomain.com` (or your configured subdomain)
2. Check the `email_messages` table in your database
3. The email should appear with status "received"

## Troubleshooting

### Emails Not Sending

1. **Check API Key:**
   - Verify the API key is correct in `config/config.php`
   - Make sure it has "Mail Send" permissions

2. **Check Sender Verification:**
   - Ensure your sender email is verified in SendGrid
   - For production, use domain authentication

3. **Check Logs:**
   - Check `email_messages` table for error messages
   - Check PHP error logs

### Emails Not Receiving

1. **Check DNS Settings:**
   - Verify all DNS records from Inbound Parse are added
   - Wait for DNS propagation (can take up to 48 hours)

2. **Check Webhook URL:**
   - Ensure the webhook URL is accessible
   - Test with: `curl -X POST https://yourdomain.com/api/email/sendgrid-webhook.php`

3. **Check Webhook Security:**
   - If using webhook verification, ensure `SENDGRID_WEBHOOK_PUBLIC_KEY` is set

## Features

### Sending Emails

- ✅ Single email sending
- ✅ Bulk email sending
- ✅ HTML and text emails
- ✅ CC and BCC support
- ✅ Attachments support
- ✅ Welcome emails
- ✅ Password reset emails
- ✅ Notification emails
- ✅ Custom emails

### Receiving Emails

- ✅ Inbound email parsing
- ✅ Email storage in database
- ✅ Admin notifications for new emails
- ✅ Email event tracking (bounce, delivered, open, click)

## API Usage Examples

### Send Simple Email

```php
require_once 'includes/SendGridService.php';
$sendgrid = new SendGridService();
$result = $sendgrid->sendSimpleEmail('user@example.com', 'Subject', 'Message text');
```

### Send HTML Email

```php
$html = '<h1>Hello</h1><p>This is HTML</p>';
$result = $sendgrid->sendHtmlEmail('user@example.com', 'Subject', $html);
```

### Send Bulk Email

```php
$recipients = ['user1@example.com', 'user2@example.com'];
$result = $sendgrid->sendBulkEmail($recipients, 'Subject', 'Text', $html);
```

## Security Notes

1. **Never commit API keys to version control**
2. **Use environment variables for sensitive data**
3. **Enable webhook verification for production**
4. **Use HTTPS for webhook URLs**
5. **Regularly rotate API keys**

## Support

For SendGrid-specific issues:
- SendGrid Documentation: https://docs.sendgrid.com/
- SendGrid Support: https://support.sendgrid.com/

For application-specific issues:
- Check application logs
- Review database `email_messages` table
- Check PHP error logs

