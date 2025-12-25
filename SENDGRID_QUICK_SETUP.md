# SendGrid Quick Setup Guide

## Why SendGrid Shows "Not Configured"

The system shows "Not Configured" because the API key in `config/config.php` is still set to the placeholder value `'YOUR_SENDGRID_API_KEY_HERE'`.

## Quick Setup (5 Minutes)

### Step 1: Create SendGrid Account (2 minutes)

1. Go to https://sendgrid.com/
2. Click "Start for Free" or "Sign Up"
3. Fill in your information
4. Verify your email address

### Step 2: Get Your API Key (2 minutes)

1. Log in to SendGrid
2. Click on **Settings** (gear icon) in the left sidebar
3. Click **API Keys**
4. Click **"Create API Key"** button
5. Fill in:
   - **API Key Name:** Bus Tracking System
   - **API Key Permissions:** Select **"Full Access"** (or "Restricted Access" with Mail Send permission)
6. Click **"Create & View"**
7. **IMPORTANT:** Copy the API key immediately (it starts with `SG.`)
   - Example: `SG.abc123xyz789...`
   - You won't be able to see it again!
   - Save it somewhere safe

### Step 3: Verify Your Sender Email (1 minute)

**Option A: Single Sender Verification (Easiest for Testing)**
1. Go to **Settings → Sender Authentication → Single Sender Verification**
2. Click **"Create New Sender"**
3. Fill in:
   - **From Email Address:** your-email@example.com (use your real email)
   - **From Name:** Your Name
   - **Reply To:** (same or different email)
4. Complete the form and click **"Create"**
5. Check your email inbox
6. Click the verification link in the email from SendGrid
7. ✅ Verification complete!

**Option B: Domain Authentication (For Production)**
- More complex, requires DNS access
- See full guide in `SENDGRID_SETUP.md`

### Step 4: Update Configuration File

1. Open `config/config.php` in a text editor
2. Find these lines (around line 21):

```php
// SendGrid Configuration (get from https://app.sendgrid.com/)
define('SENDGRID_API_KEY', 'YOUR_SENDGRID_API_KEY_HERE');
define('SENDGRID_FROM_EMAIL', 'noreply@yourdomain.com');
define('SENDGRID_FROM_NAME', 'School Bus Tracking System');
define('SENDGRID_REPLY_TO', 'support@yourdomain.com');
```

3. Replace with your actual values:

```php
// SendGrid Configuration
define('SENDGRID_API_KEY', 'SG.your_actual_api_key_here');
define('SENDGRID_FROM_EMAIL', 'your-verified-email@example.com');
define('SENDGRID_FROM_NAME', 'School Bus Tracking System');
define('SENDGRID_REPLY_TO', 'your-verified-email@example.com');
```

**Important:**
- Replace `SG.your_actual_api_key_here` with your actual API key from Step 2
- Replace `your-verified-email@example.com` with the email you verified in Step 3
- Keep the quotes around the values

4. Save the file

### Step 5: Test It!

1. Go to: `http://yourdomain.com/test-sendgrid.php`
2. The page should now show:
   - ✅ API Key: **Configured** (green badge)
   - ✅ From Email: **your-email@example.com** (green badge)
3. Enter your email address
4. Click **"Send Test Email"**
5. Check your inbox (and spam folder)
6. You should receive the test email! 🎉

## Troubleshooting

### "API Key Not Configured" Still Shows

**Check:**
1. Did you save the `config/config.php` file?
2. Did you replace `YOUR_SENDGRID_API_KEY_HERE` with your actual key?
3. Is the API key still in quotes? `'SG.your_key_here'`
4. Did you remove any extra spaces?

**Solution:**
- Double-check the file was saved
- Make sure the API key starts with `SG.`
- Clear your browser cache and refresh

### Email Not Sending

**Check:**
1. Is your sender email verified?
2. Is the API key correct?
3. Check the "Recent Email Logs" section on the test page

**Common Issues:**
- **"Sender verification required"**: You need to verify your email in SendGrid
- **"Invalid API key"**: Check that you copied the full API key correctly
- **Email in spam**: Check your spam/junk folder

### API Key Error

**If you get an error about the API key:**
1. Make sure it starts with `SG.`
2. Make sure there are no spaces before or after
3. Make sure it's in quotes: `'SG.abc123...'`
4. Try creating a new API key if the old one doesn't work

## Example Configuration

Here's what a properly configured section looks like:

```php
// SendGrid Configuration
define('SENDGRID_API_KEY', 'SG.abcdefghijklmnopqrstuvwxyz1234567890');
define('SENDGRID_FROM_EMAIL', 'admin@myschool.com');
define('SENDGRID_FROM_NAME', 'School Bus Tracking System');
define('SENDGRID_REPLY_TO', 'support@myschool.com');
```

## Security Note

⚠️ **Important:** Never commit your API key to version control (Git).
- The `.gitignore` file should exclude `config/config.php` if it contains secrets
- Or use environment variables for production

## Free Tier Limits

SendGrid Free Tier includes:
- **100 emails per day** (forever free)
- Perfect for testing and small projects
- All features available

## Need More Help?

- See `SENDGRID_SETUP.md` for detailed guide
- SendGrid Documentation: https://docs.sendgrid.com/
- SendGrid Support: https://support.sendgrid.com/

---

## Quick Checklist

- [ ] SendGrid account created
- [ ] API key created and copied
- [ ] Sender email verified
- [ ] `config/config.php` updated with API key
- [ ] `config/config.php` updated with verified email
- [ ] File saved
- [ ] Test page shows "Configured"
- [ ] Test email sent successfully
- [ ] Test email received in inbox

---

**You're all set!** Once configured, you can send emails from your School Bus Tracking System. 🚀

