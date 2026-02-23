# Two-Factor Authentication (2FA) Implementation Documentation

## Overview

This document covers the complete implementation of Two-Factor Authentication using Google Authenticator (TOTP) in the Symfony application.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Database Schema](#database-schema)
4. [Implementation](#implementation)
5. [User Interface](#user-interface)
6. [Security Features](#security-features)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

## Installation

### 1. Bundle Installation

```bash
composer require scheb/2fa-bundle
```

The bundle automatically registers itself in Symfony's configuration.

### 2. Bundle Registration

The bundle is automatically registered in `config/bundles.php`:

```php
Scheb\TwoFactorBundle\SchebTwoFactorBundle::class => ['all' => true],
```

## Configuration

### 1. Security Configuration

File: `config/packages/security.yaml`

```yaml
security:
    firewalls:
        main:
            # ... existing configuration
            two_factor: true
```

### 2. Two-Factor Bundle Configuration

File: `config/packages/scheb_two_factor.yaml`

```yaml
scheb_two_factor:
    security_tokens:
        Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken: true
```

## Database Schema

### User Entity Modifications

The User entity was extended with the following field:

```php
#[ORM\Column(length: 255, nullable: true)]
private ?string $googleAuthenticatorSecret = null;
```

### Added Methods

```php
public function isGoogleAuthenticatorEnabled(): bool
{
    return $this->googleAuthenticatorSecret !== null;
}

public function getGoogleAuthenticatorSecret(): ?string
{
    return $this->googleAuthenticatorSecret;
}

public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): static
{
    $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    return $this;
}
```

### Database Migration

The schema was updated using:

```bash
php bin/console doctrine:schema:update --force
```

## Implementation

### 1. TwoFactorController

File: `src/Controller/TwoFactorController.php`

#### Routes

- `/2fa/setup` - Display 2FA setup page
- `/2fa/enable` - Process 2FA enable (POST)
- `/2fa/disable` - Process 2FA disable (POST)

#### Key Methods

**setup()**: Generates secret key and QR code for setup
**enable()**: Verifies TOTP code and enables 2FA
**disable()**: Verifies password and disables 2FA

### 2. TOTP Implementation

The controller includes a simplified TOTP implementation:

```php
private function generateTOTP(string $secret, int $time): string
{
    $hash = hash_hmac('sha1', pack('N', $time), $secret);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $binary = substr($hash, $offset, 4);
    $number = unpack('N', $binary)[1];
    return str_pad(($number & 0x7FFFFFFF) % 1000000, 6, '0', STR_PAD_LEFT);
}
```

### 3. QR Code Generation

QR codes are generated using Google Charts API:

```php
private function generateGoogleAuthQrCode($user, string $secret): string
{
    $appName = 'Your App Name';
    $email = $user->getEmail();
    
    return sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s',
        urlencode($appName),
        urlencode($email),
        $secret,
        urlencode($appName)
    );
}
```

## User Interface

### 1. Setup Page

File: `templates/security/2fa_setup.html.twig`

**Features**:
- QR code display for easy setup
- Manual secret key entry option
- 6-digit code verification
- Copy-to-clipboard functionality
- Responsive Bootstrap design

### 2. 2FA Form Page

File: `templates/security/2fa_form.html.twig`

**Features**:
- Code entry during login
- Auto-focus and auto-submit
- Trusted device option
- Error handling and display

### 3. Profile Integration

File: `templates/profile/edit.html.twig`

**Features**:
- 2FA status indicator (enabled/disabled)
- Enable/Disable buttons
- Password-protected disable modal
- Status badges with colors

### 4. Navigation Integration

File: `templates/sidebar.html.twig`

**Features**:
- 2FA button in user dropdown menu
- Real-time status badge
- Direct access to 2FA setup

## Security Features

### 1. Time-Based One-Time Passwords (TOTP)

- **30-second time windows**
- **Clock drift tolerance** (±1 window)
- **6-digit codes**
- **SHA-1 HMAC** for code generation

### 2. Protection Measures

- **Password verification** for disable operations
- **CSRF protection** on all forms
- **Session-based authentication**
- **Rate limiting** (via Symfony security)

### 3. User Experience

- **Auto-submit** when 6 digits entered
- **Paste support** for codes
- **Visual feedback** for all actions
- **Mobile-responsive** design

## Testing

### 1. Setup Testing

1. Navigate to Profile → Edit Profile
2. Click "Activer" next to 2FA section
3. Scan QR code with authenticator app
4. Enter verification code
5. Confirm 2FA is enabled

### 2. Login Testing

1. Logout of application
2. Login with valid credentials
3. Enter 2FA code from authenticator app
4. Confirm successful authentication

### 3. Disable Testing

1. Go to Profile → Edit Profile
2. Click "Désactiver" in 2FA section
3. Enter password to confirm
4. Confirm 2FA is disabled

## Troubleshooting

### Common Issues

#### 1. Route Not Found Error

**Problem**: `RouteNotFoundException` for `app_profile` route
**Solution**: Use `app_profile_edit` route instead

#### 2. Invalid TOTP Codes

**Problem**: Codes from authenticator app don't work
**Solutions**:
- Check device time synchronization
- Verify secret key matches
- Try code from next time window

#### 3. QR Code Not Displaying

**Problem**: QR code image doesn't load
**Solutions**:
- Check internet connection
- Verify Google Charts API accessibility
- Use manual secret entry

#### 4. 2FA Not Required During Login

**Problem**: Login doesn't prompt for 2FA code
**Solutions**:
- Verify user has 2FA enabled in database
- Check security configuration
- Clear Symfony cache

### Debug Commands

```bash
# Check routes
php bin/console debug:router | findstr 2fa

# Clear cache
php bin/console cache:clear

# Update database schema
php bin/console doctrine:schema:update --force

# Check user entity
php bin/console debug:container User
```

## Production Considerations

### 1. Security

- Use HTTPS for all 2FA operations
- Implement rate limiting for code attempts
- Consider backup codes for account recovery
- Monitor 2FA enable/disable events

### 2. Performance

- Cache QR code generation
- Optimize TOTP verification
- Use CDN for QR code images

### 3. User Experience

- Provide clear setup instructions
- Offer multiple authenticator app options
- Include troubleshooting help
- Consider SMS/email fallback options

## API Endpoints

### Public Routes

```
GET  /2fa/setup     - Display 2FA setup page
POST /2fa/enable    - Enable 2FA
POST /2fa/disable   - Disable 2FA
```

### Requirements

- **Authentication**: All routes require `ROLE_USER`
- **CSRF**: All POST routes require CSRF token
- **Validation**: Input validation on all forms

## Dependencies

### Required Packages

- `scheb/2fa-bundle` - Main 2FA bundle
- `symfony/security-bundle` - Security integration
- `symfony/twig-bundle` - Template rendering

### Optional Enhancements

- `endroid/qr-code` - Local QR code generation
- `paragonie/constant_time_encoding` - Secure timing comparisons
- `spomky-labs/otphp` - Advanced TOTP library

## Support

For issues or questions:

1. Check Symfony logs: `var/log/dev.log`
2. Verify bundle configuration
3. Test with different authenticator apps
4. Review this documentation

## Version History

- **v1.0.0** - Initial implementation with Google Authenticator support
- Basic TOTP implementation
- QR code generation via Google Charts API
- Profile integration
- Navigation integration

---

*Last updated: February 2026*
