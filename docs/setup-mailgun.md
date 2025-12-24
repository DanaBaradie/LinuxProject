# Mailgun Setup Guide

## Overview
This guide will help you set up Mailgun for sending and receiving emails in the Bus Tracking System.

## Step 1: Create a Mailgun Account

1. Go to [https://www.mailgun.com/](https://www.mailgun.com/)
2. Click "Sign Up" and create a free account
3. Verify your email address

## Step 2: Get Your API Credentials

### Option A: Using Sandbox Domain (For Testing)

1. Log in to your Mailgun dashboard
2. Go to **Sending** → **Overview**
3. Find your **Sandbox domain** (looks like: `sandboxXXXXXXXX.mailgun.org`)
4. Copy your **API Key** from the same page
5. **Note**: Sandbox domains can only send to authorized recipients

### Option B: Using Your Own Domain (For Production)

1. Go to **Sending** → **Domains**
2. Click "Add New Domain"
3. Enter your domain name (e.g., `mg.yourdomain.com`)
4. Follow the DNS configuration instructions:
   - Add TXT records for domain verification
   - Add MX records for receiving emails
   - Add CNAME records for tracking
5. Wait for DNS propagation (can take up to 48 hours)
6. Once verified, copy your **API Key**

## Step 3: Configure the Application

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` and add your Mailgun credentials:
   ```env
   MAILGUN_API_KEY=your_api_key_here
   MAILGUN_DOMAIN=your_domain.mailgun.org
   MAILGUN_FROM_EMAIL=noreply@yourdomain.com
   MAILGUN_FROM_NAME=Bus Tracking System
   ```

3. Make sure `.env` is in your `.gitignore` file

## Step 4: Install Dependencies

If using Composer (recommended):
```bash
composer install
```

If not using Composer, the system will use direct API calls (already implemented).

## Step 5: Set Up Database

Run the email schema SQL file:
```bash
mysql -u your_username -p your_database < database/email_schema.sql
```

Or import it via phpMyAdmin.

## Step 6: Configure Webhook for Receiving Emails

1. In Mailgun dashboard, go to **Sending** → **Webhooks**
2. Click "Add Webhook"
3. Select event type: **Incoming Messages**
4. Enter your webhook URL:
   ```
   https://yourdomain.com/api/email/webhook.php
   ```
5. Save the webhook

## Step 7: Authorize Recipients (Sandbox Only)

If using sandbox domain:

1. Go to **Sending** → **Overview**
2. Scroll to "Authorized Recipients"
3. Add email addresses that should receive test emails
4. Recipients must click the confirmation link in their email

## Step 8: Test the Integration

1. Create a new user in the system
2. Check if welcome email was sent
3. Try sending a custom email from the users page
4. Check the messages page to see email logs

## Troubleshooting

### Emails Not Sending

- **Check API Key**: Make sure it's correct in `.env`
- **Check Domain**: Verify domain is active in Mailgun
- **Check Logs**: Look at `email_messages` table for error messages
- **Sandbox Limits**: Remember sandbox can only send to authorized recipients

### Emails Going to Spam

- **SPF/DKIM**: Make sure DNS records are properly configured
- **Domain Reputation**: Use a verified domain, not sandbox
- **Content**: Avoid spam trigger words

### Webhook Not Working

- **URL Accessibility**: Make sure webhook URL is publicly accessible
- **HTTPS**: Mailgun requires HTTPS for webhooks in production
- **Signature Verification**: Check that signature verification is working

## Pricing

- **Free Tier**: 5,000 emails/month for 3 months
- **Pay As You Go**: $0.80 per 1,000 emails after free tier
- **Foundation Plan**: $35/month for 50,000 emails

## Security Best Practices

1. **Never commit `.env` file** to version control
2. **Use environment variables** for sensitive data
3. **Rotate API keys** periodically
4. **Enable webhook signature verification** (already implemented)
5. **Use HTTPS** in production

## Additional Resources

- [Mailgun Documentation](https://documentation.mailgun.com/)
- [API Reference](https://documentation.mailgun.com/en/latest/api_reference.html)
- [Webhook Guide](https://documentation.mailgun.com/en/latest/user_manual.html#webhooks)

## Support

If you encounter issues:
1. Check Mailgun logs in dashboard
2. Check application logs in `email_messages` table
3. Contact Mailgun support
4. Review this documentation
