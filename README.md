# moodle-fileconverter_gotenberg

A Moodle `fileconverter` plugin that converts documents to PDF via a
[Gotenberg](https://gotenberg.dev) container, instead of `unoconv`.

`unoconv` is deprecated and its intended successor, `unoserver`, isn't
packaged/available everywhere yet. Gotenberg wraps LibreOffice behind a small
HTTP API and is easy to run as a container, so this plugin just forwards the
file Moodle wants converted to Gotenberg and stores the PDF it gets back.

## What it does

- Registers a `fileconverter` plugin that Moodle can use anywhere it needs to
  convert a document to PDF (e.g. annotating assignment submissions).
- Sends the source file to Gotenberg's `/forms/libreoffice/convert` route and
  stores the returned PDF as the conversion's result.
- Supports converting `doc`, `docx`, `odt`, `ott`, `rtf`, `txt`, `html`,
  `htm`, `xls`, `xlsx`, `ods`, `ots`, `csv`, `ppt`, `pptx`, `odp` and `otp`
  files to PDF.
- Ships a settings page to configure the Gotenberg URL and request timeout,
  plus a connection test page to verify everything is wired up correctly.

## Requirements

- A running Gotenberg instance reachable from your Moodle server.
- The PHP `curl` extension (already required by Moodle itself).

## Running Gotenberg

```yaml
services:
  gotenberg:
    image: gotenberg/gotenberg:8
    restart: unless-stopped
    ports:
      - "3000:3000"
```

If Gotenberg runs on a private/internal network (e.g. a Docker container next
to Moodle), Moodle's own HTTP security settings will block the request by
default. Under *Site administration > Security > HTTP security*, add
Gotenberg's port (e.g. `3000`) to **Allowed ports**, and make sure its host
or IP range isn't covered by **Blocked hosts and IP addresses** (which blocks
private ranges like `172.16.0.0/12` out of the box).

## Installation

1. Copy (or clone) this repository into `<moodle>/files/converter/gotenberg`.
2. Visit *Site administration > Notifications* to complete the plugin install.
3. Go to *Site administration > Plugins > Document converters > Gotenberg*
   and set the **Gotenberg URL** (e.g. `http://gotenberg:3000`).
4. Use the **Test Gotenberg connection** link on that settings page to check
   connectivity and download a sample converted PDF.
5. Enable the plugin under *Site administration > Plugins > Document
   converters > Manage document converters*.

## License

GPL v3 or later, see [LICENSE](LICENSE).
