# VS Code: Xdebug Setup and Troubleshooting Guide

## How to Use

### Step 1: Rebuild and Start Docker Container
(Re)build and start containers:
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Step 2: Set a Breakpoint
In VS Code, click on the line number in your code to set a breakpoint.

### Step 3: Start Debugging
1. Open the Debug panel in VS Code (Ctrl+Shift+D)
2. Select "Listen for Xdebug" from the dropdown
3. Press F5 to start listening for connections

### Step 4: Trigger Your Application
Visit http://localhost:8080 in your browser to trigger a request. The debugger should now pause at your breakpoints.

## Troubleshooting

### Issue: Debugger still not stopping at breakpoints

**Check if Xdebug is connecting:**
```bash
docker exec php tail -f /var/www/html/xdebug.log
```

This will show you log entries. Look for entries like:
```
[1] Log opened at 2024-01-15 10:30:45.123456
```

If you see connection attempts, it's working. If not, check:

#### 1. **Windows Firewall**
- Xdebug needs to connect from the Docker container to your host on port 9003
- Add an exception in Windows Firewall for port 9003, or
- Temporarily disable the firewall to test

#### 2. **VS Code PHP Extension**
- Make sure you have the "PHP Debug" extension installed (felixbeckercom.php-debug)
- Verify it's enabled

#### 3. **Port Conflicts**
- Ensure port 9003 is not already in use:
```powershell
netstat -ano | findstr :9003
```

#### 4. **Environment Check**
- Verify the container is running:
```bash
docker ps | findstr php
```

- Check the Xdebug configuration inside the container:
```bash
docker exec php php -i | findstr -i xdebug
```

You should see:
```
xdebug.client_host => host.docker.internal
xdebug.client_port => 9003
xdebug.mode => debug
```

### Issue: Too much logging

If the xdebug.log file becomes too large, clear it:
```bash
docker exec php rm -f /var/www/html/xdebug.log
```

## Additional Tips

1. **Variable Inspection**: Hover over variables in the editor to see their values
2. **Step Controls**:
   - F10: Step over
   - F11: Step into
   - Shift+F11: Step out
3. **Debug Console**: Use it to evaluate PHP expressions in real-time
4. **Conditional Breakpoints**: Right-click a breakpoint to add conditions

## Still Having Issues?

1. Check the xdebug.log file for specific error messages
2. Ensure `host.docker.internal` resolves correctly (on Windows Docker Desktop it should)
3. Try an alternative: Set `xdebug.discover_client_host=0` if having connection issues

