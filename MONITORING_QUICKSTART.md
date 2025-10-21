# Monitoring Quick Setup Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Run the Demo (30 seconds)

```bash
php examples/monitoring_demo.php
```

This will:
- ✅ Create storage directories
- ✅ Record sample metrics
- ✅ Trigger sample alerts
- ✅ Display health status
- ✅ Show statistics
- ✅ Export metrics

### Step 2: View the Dashboard (1 minute)

1. Start your local server:
   ```bash
   php -S localhost:8000
   ```

2. Open in browser:
   ```
   http://localhost:8000/dashboard.html
   ```

3. You'll see:
   - Health status with score
   - Request/response metrics
   - Performance stats
   - Recent alerts
   - System metrics

### Step 3: Check Health Endpoint (30 seconds)

```bash
# JSON format
curl http://localhost:8000/health.php

# Prometheus format
curl http://localhost:8000/health.php?format=prometheus
```

### Step 4: Configure (2 minutes)

1. Copy example config:
   ```bash
   cp config/monitoring.example.php config/monitoring.php
   ```

2. Edit thresholds in `config/api.php`:
   ```php
   'monitoring' => [
       'enabled' => true,
       'thresholds' => [
           'error_rate' => 5.0,      // Adjust as needed
           'response_time' => 1000,  // Adjust as needed
       ],
   ],
   ```

### Step 5: Integrate into Router (1 minute)

Follow `MONITOR_INTEGRATION_GUIDE.php` to add monitoring to your Router class.

Key changes:
1. Add Monitor property
2. Initialize in constructor
3. Record requests/responses
4. Record security events
5. Record errors

## 🎯 Done!

Your API now has:
- ✅ Real-time monitoring
- ✅ Health checks
- ✅ Alerting system
- ✅ Visual dashboard
- ✅ Prometheus metrics

## 📚 Next Steps

- Read full docs: `docs/MONITORING.md`
- Configure alert handlers: `examples/alert_handlers.php`
- Set up Prometheus: See docs for scrape config
- Create Grafana dashboard: Use exported metrics
- Set up automated cleanup: Add cron job

## 🔗 Quick Links

- **Demo**: `examples/monitoring_demo.php`
- **Dashboard**: `dashboard.html`
- **Health Check**: `health.php`
- **Docs**: `docs/MONITORING.md`
- **Integration**: `MONITOR_INTEGRATION_GUIDE.php`
- **Summary**: `MONITORING_IMPLEMENTATION.md`

## 💡 Tips

1. **Start with defaults** - They're production-ready
2. **Adjust thresholds** based on your traffic patterns
3. **Set up alerts** early to catch issues
4. **Monitor the monitor** - Use external uptime checks
5. **Review metrics** regularly to optimize

## ❓ Need Help?

- Check `docs/MONITORING.md` for detailed documentation
- Run `examples/monitoring_demo.php` to see it in action
- Check troubleshooting section in docs

---

**You're all set!** 🎉 Your API monitoring is ready to go!
