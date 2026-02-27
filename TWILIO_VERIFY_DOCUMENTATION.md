# Twilio Verify API Integration Documentation

## Overview

This document provides comprehensive information about the Twilio Verify API integration implemented in the Symfony 6.4 application. The integration adds SMS-based phone verification capabilities to enhance user security and account validation.

## Features Implemented

### 1. Phone Verification Service
- **Service Class**: `App\Service\TwilioVerifyService`
- **Purpose**: Handles all Twilio Verify API interactions
- **Capabilities**:
  - Send verification codes via SMS
  - Verify submitted codes
  - Phone number validation (Tunisian numbers)
  - E.164 format conversion
  - Comprehensive error handling and logging

### 2. Phone Verification Controller
- **Controller Class**: `App\Controller\TwilioVerifyController`
- **Routes**:
  - `/api/phone-verification/send` - Send verification code
  - `/api/phone-verification/verify` - Verify submitted code
  - `/phone-verification` - Phone verification page
  - `/profile/phone-verification/enable` - Enable phone verification for logged-in users
  - `/profile/phone-verification/disable` - Disable phone verification
  - `/profile/phone-verification/confirm` - Confirm phone verification

### 3. Database Schema Updates
- **New Fields Added to User Entity**:
  - `phone_verified` (BOOLEAN) - Track verification status
  - `phone_verified_at` (DATETIME) - Store verification timestamp

### 4. Frontend Integration
- **Phone Verification Page**: Standalone verification interface
- **Registration Form**: Integrated phone verification during signup
- **Profile Management**: Phone verification controls in user profile

## Technical Implementation

### Phone Number Validation

The system validates Tunisian phone numbers with the following logic:
- **Format**: 8 digits without country code
- **Valid Prefixes**: 20-29, 30-39, 40-49, 50-59, 70-79, 90-99
- **E.164 Conversion**: Automatically adds +216 prefix for API calls

### Verification Flow

1. **Send Code**:
   ```javascript
   POST /api/phone-verification/send
   Body: { "phone": "20123456" }
   ```

2. **Verify Code**:
   ```javascript
   POST /api/phone-verification/verify
   Body: { "code": "123456" }
   ```

3. **Response Format**:
   ```json
   {
     "success": true,
     "message": "Code de vérification envoyé avec succès",
     "sid": "VAxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
   }
   ```

### Security Features

- **Session Management**: Verification state stored in secure session
- **Rate Limiting**: Prevents abuse through Twilio's built-in limits
- **CSRF Protection**: All forms protected with CSRF tokens
- **Password Confirmation**: Sensitive operations require password verification
- **Logging**: Comprehensive audit trail for all verification attempts

## Configuration

### Environment Variables

Add these variables to your `.env.local` file:

```bash
# Twilio Verify API Configuration
TWILIO_ACCOUNT_SID=your_twilio_account_sid_here
TWILIO_AUTH_TOKEN=your_twilio_auth_token_here
TWILIO_VERIFY_SERVICE_SID=your_twilio_verify_service_sid_here
```

### Service Configuration

The Twilio service is configured in `config/services/twilio.yaml`:

```yaml
services:
    App\Service\TwilioVerifyService:
        arguments:
            $accountSid: '%env(TWILIO_ACCOUNT_SID)%'
            $authToken: '%env(TWILIO_AUTH_TOKEN)%'
            $verifyServiceSid: '%env(TWILIO_VERIFY_SERVICE_SID)%'
            $logger: '@logger'
        tags:
            - { name: monolog.logger, channel: twilio }
```

## Setup Instructions

### 1. Twilio Account Setup

1. **Create Twilio Account**:
   - Visit https://www.twilio.com
   - Sign up for a free trial account
   - Verify your email and phone number

2. **Create Verify Service**:
   - Go to Twilio Console → Verify → Services
   - Create a new Verify Service
   - Configure friendly name and message templates
   - Note the Service SID (starts with 'VA')

3. **Get Account Credentials**:
   - Account SID: Available in Console Dashboard
   - Auth Token: Available in Console Dashboard
   - Verify Service SID: From your Verify Service settings

### 2. Application Configuration

1. **Update Environment**:
   ```bash
   cp .env.local.template .env.local
   # Edit .env.local with your Twilio credentials
   ```

2. **Install Dependencies**:
   ```bash
   composer require twilio/sdk
   ```

3. **Run Database Migration**:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

4. **Clear Cache**:
   ```bash
   php bin/console cache:clear
   ```

### 3. Testing Configuration

1. **Test Phone Verification**:
   - Navigate to `/phone-verification`
   - Enter a Tunisian phone number
   - Click "Send verification code"
   - Check for SMS receipt
   - Enter the received code

2. **Test Registration Integration**:
   - Go to `/register`
   - Fill out the registration form
   - Click "Vérifier le numéro" next to phone field
   - Complete the verification process

## API Endpoints

### Send Verification Code

**Endpoint**: `POST /api/phone-verification/send`

**Request Body**:
```json
{
  "phone": "20123456"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Code de vérification envoyé avec succès",
  "sid": "VAxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Error Response**:
```json
{
  "success": false,
  "message": "Format de numéro de téléphone invalide"
}
```

### Verify Code

**Endpoint**: `POST /api/phone-verification/verify`

**Request Body**:
```json
{
  "code": "123456"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Code vérifié avec succès",
  "status": "approved"
}
```

**Error Response**:
```json
{
  "success": false,
  "message": "Code invalide. Veuillez réessayer."
}
```

## Frontend Components

### Phone Verification Page

**Location**: `templates/security/phone_verification.html.twig`

**Features**:
- Step-by-step verification process
- Real-time validation
- Loading states and error handling
- Responsive design
- Code resend functionality

### Registration Integration

**Location**: `templates/registration/register.html.twig`

**Features**:
- Inline phone verification
- Visual feedback for verification status
- Prevents form submission without phone verification
- Seamless user experience

### Profile Management

**Location**: `templates/profile/edit.html.twig` (to be implemented)

**Features**:
- Enable/disable phone verification
- Change verified phone number
- Password-protected operations
- Verification history

## Security Considerations

### 1. Phone Number Privacy
- Phone numbers are stored securely in the database
- Verification codes are never stored
- Session data is automatically cleaned up

### 2. Rate Limiting
- Twilio provides built-in rate limiting
- Additional application-level limits can be implemented
- Prevents SMS bombing and abuse

### 3. Code Security
- Verification codes expire after 10 minutes (Twilio default)
- Codes are single-use only
- Failed attempts are logged and monitored

### 4. Session Security
- Verification state stored in secure HTTP-only sessions
- Automatic session cleanup after verification
- CSRF protection on all endpoints

## Error Handling

### Common Error Scenarios

1. **Invalid Phone Number**:
   - Message: "Format de numéro de téléphone invalide"
   - Solution: Ensure 8-digit Tunisian format

2. **Twilio API Error**:
   - Message: "Erreur lors de l'envoi du code de vérification"
   - Solution: Check Twilio credentials and service status

3. **Expired Code**:
   - Message: "Code expiré. Veuillez demander un nouveau code"
   - Solution: Request a new verification code

4. **Network Issues**:
   - Message: "Erreur de connexion. Veuillez réessayer"
   - Solution: Check internet connection and retry

### Logging

All verification attempts are logged with:
- Timestamp
- Phone number (masked)
- Success/failure status
- Error messages
- IP address

## Production Considerations

### 1. Twilio Pricing
- Verify API costs per verification
- SMS costs vary by country
- Monitor usage in Twilio Console

### 2. Phone Number Validation
- Currently optimized for Tunisian numbers
- Can be extended for international numbers
- Update validation rules in `TwilioVerifyService`

### 3. Monitoring and Analytics
- Track verification success rates
- Monitor Twilio API usage
- Set up alerts for high failure rates

### 4. Compliance
- Ensure GDPR compliance for phone data
- Provide opt-out mechanisms
- Document data retention policies

## Troubleshooting

### Common Issues

1. **SMS Not Received**:
   - Check phone number format
   - Verify Twilio service is active
   - Check carrier delivery issues

2. **Verification Fails**:
   - Ensure correct code entry
   - Check for expired codes
   - Verify system time sync

3. **Configuration Errors**:
   - Validate environment variables
   - Check service configuration
   - Verify database schema

### Debug Commands

```bash
# Check Twilio service configuration
php bin/console debug:container TwilioVerifyService

# Verify database schema
php bin/console doctrine:schema:validate

# Check routing
php bin/console debug:router phone_verification_send
```

## Future Enhancements

### Planned Features

1. **Multi-language Support**:
   - SMS templates in multiple languages
   - Localized error messages
   - Region-specific phone validation

2. **Advanced Analytics**:
   - Verification success metrics
   - Geographic distribution
   - Carrier performance analysis

3. **Alternative Verification Methods**:
   - WhatsApp verification
   - Voice call verification
   - Email verification fallback

4. **Enhanced Security**:
   - Device fingerprinting
   - Risk-based verification
   - Machine learning fraud detection

## Support and Maintenance

### Regular Maintenance Tasks

1. **Monitor Twilio Usage**:
   - Check monthly billing
   - Review verification success rates
   - Update phone number validation rules

2. **Update Dependencies**:
   - Keep Twilio SDK current
   - Update Symfony components
   - Apply security patches

3. **Backup and Recovery**:
   - Regular database backups
   - Configuration backups
   - Disaster recovery testing

### Contact Information

For technical support or questions about this integration:
- **Documentation**: This file
- **Code Review**: Review the implementation in the repository
- **Twilio Support**: https://www.twilio.com/help/contact

---

**Last Updated**: February 24, 2026
**Version**: 1.0.0
**Symfony Version**: 6.4
**Twilio SDK Version**: 6.x
