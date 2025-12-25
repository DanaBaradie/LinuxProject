# How to Test SendGrid Integration

This guide will help you test SendGrid email sending and receiving functionality.

## Quick Start Testing

### Method 1: Using the Test Page (Recommended)

1. **Access the Test Page:**
   - Log in as admin
   - Go to: `http://yourdomain.com/test-sendgrid.php`
   - Or click "Test SendGrid" in the sidebar

2. **Check Configuration:**
   - The page will show if SendGrid is configured
   - Verify API key status
   - Check from email settings

3. **Send a Test Email:**
   - Enter your email address in "Test Email Address"
   - Enter a subject (or use default)
   - Enter a message (or use default)
   - Click "Send Test Email"
   - Check your inbox!

4. **Test Different Email Types:**
   - Click "Test" buttons for:
     - Welcome Email
     - Password Reset Email
     - Notification Email

### Method 2: Using the API Directly

#### Test via cURL:

```bash
curl -X POST http://yourdomain.com/api/email/send.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "to=your-email@example.com&subject=Test&message=Hello from SendGrid&type=custom" \
  -b "PHPSESSID=your_session_id"
```

#### Test via JavaScript (Browser Console):

```javascript
fetch('/api/email/send.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        to: 'your-email@example.com',
        subject: 'Test Email',
        message: 'This is a test',
        type: 'custom'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Method 3: Test Password Reset Email

```bash
curl -X POST http://yourdomain.com/api/email/send-password-reset.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=your-email@example.com" \
  -b "PHPSESSID=your_session_id"
```

## Step-by-Step Testing Guide

### Step 1: Install SendGrid Package

```bash
cd /path/to/your/project
composer install
```

This installs the SendGrid PHP library.

### Step 2: Configure SendGrid

1. **Get API Key:**
   - Sign up at https://sendgrid.com/
   - Go to Settings → API Keys
   - Create API Key with "Mail Send" permission
   - Copy the key (starts with `SG.`)

2. **Update Configuration:**
   Edit `config/config.php`:
   ```php
   define('SENDGRID_API_KEY', 'SG.your_actual_api_key_here');
   define('SENDGRID_FROM_EMAIL', 'noreply@yourdomain.com');
   define('SENDGRID_FROM_NAME', 'School Bus Tracking System');
   ```

3. **Verify Sender:**
   - Go to SendGrid → Settings → Sender Authentication
   - Verify your sender email or domain
   - This is required for sending emails

### Step 3: Test Basic Email Sending

1. **Via Test Page:**
   - Navigate to `/test-sendgrid.php`
   - Fill in the form
   - Click "Send Test Email"
   - Check your inbox

2. **What to Check:**
   - ✅ Email arrives in inbox
   - ✅ Email is not in spam
   - ✅ Sender shows correctly
   - ✅ HTML formatting works (if using HTML)

### Step 4: Test Different Email Types

#### Welcome Email:
- Click "Test" button for Welcome Email
- Should receive formatted welcome email

#### Password Reset:
- Click "Test" button for Password Reset
- Should receive password reset link
- Link should work (if reset page exists)

#### Notification:
- Click "Test" button for Notification
- Should receive formatted notification

### Step 5: Check Email Logs

1. **On Test Page:**
   - Scroll to "Recent Email Logs" section
   - See all sent/received emails
   - Check status (sent, failed, received)

2. **In Database:**
   ```sql
   SELECT * FROM email_messages ORDER BY created_at DESC LIMIT 10;
   ```

### Step 6: Test Email Receiving (Optional)

1. **Set Up Inbound Parse:**
   - Go to SendGrid → Settings → Inbound Parse
   - Add host: `mail.yourdomain.com`
   - Set destination: `https://yourdomain.com/api/email/sendgrid-webhook.php`
   - Add DNS records to your domain

2. **Test Receiving:**
   - Send email to: `mail@yourdomain.com`
   - Check `email_messages` table
   - Should appear with status "received"

## Troubleshooting

### Email Not Sending

**Problem:** Email not arriving

**Solutions:**
1. **Check API Key:**
   - Verify API key is correct
   - Check it has "Mail Send" permission
   - Try regenerating the key

2. **Check Sender Verification:**
   - Sender email must be verified in SendGrid
   - Check SendGrid dashboard for verification status

3. **Check Spam Folder:**
   - Emails might be in spam
   - Check spam/junk folder

4. **Check Error Logs:**
   - Look at "Recent Email Logs" on test page
   - Check for error messages
   - Review PHP error logs

5. **Check SendGrid Dashboard:**
   - Go to SendGrid → Activity
   - See if email was sent
   - Check for bounce/delivery issues

### API Key Error

**Problem:** "SendGrid API key not configured"

**Solution:**
- Make sure `SENDGRID_API_KEY` is set in `config/config.php`
- API key should start with `SG.`
- No spaces or extra characters

### Sender Not Verified

**Problem:** "Sender verification required"

**Solution:**
- Go to SendGrid → Settings → Sender Authentication
- Verify your sender email
- Or authenticate your domain

### Email in Spam

**Problem:** Emails going to spam

**Solutions:**
1. Verify sender domain (not just email)
2. Set up SPF/DKIM records
3. Use a reputable domain
4. Avoid spam trigger words

## Testing Checklist

- [ ] SendGrid package installed (`composer install`)
- [ ] API key configured in `config/config.php`
- [ ] Sender email verified in SendGrid
- [ ] Test page accessible (`/test-sendgrid.php`)
- [ ] Basic email sending works
- [ ] Welcome email works
- [ ] Password reset email works
- [ ] Notification email works
- [ ] Email logs showing in database
- [ ] No errors in PHP logs
- [ ] Emails arriving in inbox (not spam)

## Advanced Testing

### Test Bulk Email:

```php
require_once 'includes/SendGridService.php';
$sendgrid = new SendGridService();
$recipients = ['email1@example.com', 'email2@example.com'];
$result = $sendgrid->sendBulkEmail($recipients, 'Subject', 'Text', '<html>HTML</html>');
```

### Test with CC/BCC:

Use the test page form or API with:
```php
$result = $sendgrid->sendEmail(
    'to@example.com',
    'Subject',
    'Text',
    '<html>HTML</html>',
    [],
    ['cc@example.com'],  // CC
    ['bcc@example.com'] // BCC
);
```

### Check Email Statistics:

```php
$sendgrid = new SendGridService();
$stats = $sendgrid->getEmailStats(30); // Last 30 days
print_r($stats);
```

## Success Indicators

✅ **Email Sending Works If:**
- Test email arrives in inbox
- Status shows "sent" in logs
- No error messages
- SendGrid dashboard shows sent emails

✅ **Email Receiving Works If:**
- Emails sent to your domain arrive
- Status shows "received" in logs
- Admin gets notifications
- Email content stored in database

## Next Steps

After successful testing:
1. Configure production sender domain
2. Set up SPF/DKIM records
3. Configure webhook for email events
4. Set up inbound email routing
5. Monitor email delivery rates

## Support

- **SendGrid Docs:** https://docs.sendgrid.com/
- **SendGrid Support:** https://support.sendgrid.com/
- **Test Page:** `/test-sendgrid.php` in your application

