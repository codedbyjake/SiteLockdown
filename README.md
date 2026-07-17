# SiteLockdown

A MediaWiki extension that lets staff instantly restrict editing during a spam or vandalism attack, without touching server config or restarting anything.

## How it works

Activating lockdown blocks editing and account creation for anonymous users, temporary accounts, and any registered account that hasn't reached autoconfirmed status yet. Autoconfirmed users and above are unaffected. The change takes effect on the very next page load and is reversed just as instantly.

## Pages

- **Special:SiteLockdown**: shows the current status and a button to activate or deactivate it. Only available to users with the `sitelockdown` permission.

## Permissions

- `sitelockdown`: activate and deactivate lockdown. Given to a new `sitelockdown-manager` group by default.
- `sitelockdown-exempt`: keep editing while lockdown is active. Give this to any group that should stay unaffected, for example:

```php
$wgGroupPermissions['autoconfirmed']['sitelockdown-exempt'] = true;
```

To let an existing group toggle lockdown instead of creating a new one:

```php
$wgGroupPermissions['sysop']['sitelockdown'] = true;
```

## License

MIT
