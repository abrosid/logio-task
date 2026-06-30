# PHPStorm: Xdebug Docker Debugging Guide

## Prerequisites

Before starting, ensure you have:
- [PHPStorm](https://www.jetbrains.com/phpstorm/) installed
- [Docker](https://www.docker.com/) & [Docker Desktop](https://www.docker.com/products/docker-desktop/) running
- The [Xdebug](https://xdebug.org/) extension properly configured in your Docker image

## Step 0: (Re)build and Start Docker Container

(Re)build the PHP container with the Xdebug configuration:

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

Verify Xdebug is running:
```bash
docker exec php php -i | findstr xdebug
```

You should see xdebug info with these key settings:
```
xdebug.mode => debug
xdebug.start_with_request => yes
xdebug.client_host => host.docker.internal
xdebug.client_port => 9003
xdebug.idekey => phpstorm
xdebug.discover_client_host => 0
```

## Step 1: Configure PHPStorm Server

1. **Open PHPStorm Settings**:
   - File → Settings (Windows/Linux) or PHPStorm → Settings (Mac)
   - Or press: `Ctrl+Alt+S` (Windows/Linux) or `Cmd+,` (Mac)

2. **Navigate to Languages & Frameworks → PHP → Servers**:
   - Click "+" to add a new server
   - Name: `Docker` (or any name)
   - Host: `localhost`
   - Port: `8080` (your APP_PORT from .env)
   - Debugger: `Xdebug`

3. **Set Path Mappings** (IMPORTANT!):
   - In the "Absolute path on the server" column, map your local project to the container path:
     - Local path: `/path/to/your/logio-task/nette`
     - Server path: `/var/www/html`

4. **Apply and OK**

## Step 2: Configure Xdebug in PHPStorm

1. Navigate to: **Languages & Frameworks → PHP → Debug**

2. Set the following:
   - **Debug port**: `9003`
   - **Debug engine**: `Xdebug`

3. (Optional but recommended) Under **DBGp Proxy**:
   - Host: `127.0.0.1`
   - Port: `9003`
   - IDE key: `phpstorm`

4. Apply and OK

## Step 3: Enable Debug Mode Listener

1. In PHPStorm, go to **Run → Edit Configurations**

2. Click "+" to add a new configuration, select **PHP Remote Debug**:
   - Name: `Docker Xdebug`
   - Servers: Select the `Docker` server you created
   - IDE key: `phpstorm`

3. Click OK

## Step 4: Start Debugging

1. **Set a breakpoint** by clicking on the line number (a red circle appears)

2. **Start listening for connections**:
   - Go to **Run → Start Listening for PHP Debug Connections**
   - Or click the phone icon in the toolbar (should turn green)

3. **Access your application**:
   - Open browser to: http://localhost:8080
   - Navigate to trigger the breakpoint

4. **PHPStorm should pause** at your breakpoint and show the debug panel

## Troubleshooting

### Issue 1: Breakpoint Not Hit

**Check if Xdebug is communicating:**
```bash
docker exec php tail -f /var/www/html/xdebug.log
```

Look for success messages or connection errors.

**Solution steps:**
1. Verify the server configuration in PHPStorm (Run → Edit Configurations)
2. Check that path mapping is correct
3. Ensure "Start Listening for PHP Debug Connections" is active (phone icon is green)

### Issue 2: "Cannot connect" Error

This usually means PHPStorm can't receive the connection from Docker.

**Solutions:**
1. **Windows Firewall**: Add exception for port 9003
   ```powershell
   # Run as Administrator
   New-NetFirewallRule -DisplayName "Xdebug" -Direction Inbound -LocalPort 9003 -Protocol TCP -Action Allow
   ```

2. **Alternative host setting** (if host.docker.internal doesn't work):
   - Check your Windows Docker IP:
     ```bash
     docker exec php ping host.docker.internal
     ```
   - If it fails, use your actual Windows IP instead (e.g., `192.168.x.x`)
   - Update Dockerfile and rebuild

3. **Disable firewall temporarily** to test:
   ```powershell
   # PowerShell as Administrator
   Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled $False
   # Then re-enable after testing
   Set-NetFirewallProfile -Profile Domain,Public,Private -Enabled $True
   ```

### Issue 3: Wrong Path Mapping

**Symptom**: PHPStorm shows code but doesn't recognize file locations

**Fix**:
1. Set breakpoint in HomePresenter.php
2. When debugger hits it, check the "Debug" panel path
3. It should show `/var/www/html/app/Presentation/Home/HomePresenter.php`
4. If path is wrong, update the path mapping in **Run → Edit Configurations**

### Issue 4: "Waiting for incoming connection"

This means PHPStorm is listening but not receiving debug connections.

**Check**:
1. Container is running: `docker ps`
2. Xdebug is enabled in PHP: `docker exec php php -i | grep xdebug`
3. Port 9003 is accessible: `netstat -ano | findstr :9003`

### Issue 5: Xdebug Log Shows No Connection Attempts

This indicates the request isn't triggering Xdebug at all.

**Solutions**:
1. Verify `xdebug.start_with_request=yes` is set
2. Clear PHP cache:
   ```bash
   docker exec php rm -rf /var/www/html/temp/cache/*
   ```
3. Restart the container:
   ```bash
   docker-compose restart
   ```

## Advanced Options

### Browser Helper Extension (Optional)

Use Xdebug Helper extension to control when debugging is active:
- **Chrome**: [Xdebug Helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplglelx27910146774735150b)
- **Firefox**: [Xdebug Helper](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/)

After installing, click the extension icon and select "Debug" mode to start debugging specific requests.

### Environment Variables

If you want to control Xdebug via environment variables, you can also set:
```bash
XDEBUG_CONFIG="idekey=phpstorm client_host=host.docker.internal client_port=9003"
```

## Testing Your Setup

1. Add this simple test to `HomePresenter.php`:
   ```php
   public function actionDefault(): void
   {
       $a = 1;        // Set breakpoint here
       $b = 2;
       $c = $a + $b;
   }
   ```

2. Set a breakpoint on line 12 (`$a = 1;`)

3. Start listening (green phone icon)

4. Visit http://localhost:8080

5. PHPStorm should pause and show variable values

## Quick Reference

| Component | Value |
|-----------|-------|
| Server Name | Docker |
| Host | localhost |
| Port | 8080 |
| Path (Local) | C:\Users\abror\logio-task\nette |
| Path (Server) | /var/www/html |
| Xdebug Port | 9003 |
| IDE Key | phpstorm |

## Still Not Working?

1. **Clear all caches**:
   ```bash
   docker-compose down
   docker volume prune
   docker-compose up -d
   ```

2. **Check Windows Docker Desktop settings**:
   - Ensure "Expose daemon on tcp://localhost:2375" is NOT checked (unless you need it)
   - Check networking is set to "nat" or "bridge"

3. **Review xdebug.log** for specific error messages:
   ```bash
   docker exec php cat /var/www/html/xdebug.log
   ```

4. **Try with simpler code** - sometimes Nette routing obscures issues

5. **Check PHPStorm Event Log**:
   - Help → Show Log in Explorer
   - Look for debug-related messages

