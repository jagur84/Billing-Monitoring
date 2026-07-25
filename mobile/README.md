# Modal Nekad — Mobile (Flutter, Android)

Companion Android app for the Modal Nekad ISP billing platform. First screen sets
the backend's base URL, then login, then a permission-driven drawer with a
role-aware Dashboard and read-only lists (Pelanggan, Tagihan, Pembayaran, Tiket,
Paket, Inventaris, Pengeluaran & Operasional, Laporan Pendapatan).

This is a **view-only v1** — no create/edit/delete from the phone yet, and no
MikroTik/GenieACS live-monitoring screens (those involve real-time router polling,
a separate, larger effort). Everything else mirrors the web app's menus and
permissions 1:1.

## Prerequisites

- **Flutter SDK** 3.44.2 or newer (stable channel)
- **Android SDK** (command-line tools + platform-tools + an emulator image, either
  standalone or bundled inside Android Studio)
- The Laravel backend running and reachable (see the root `README.md`) — this app
  is a pure API client, it does not run any backend logic itself

Installation steps for both SDKs follow below (Windows-focused, since that's this
project's dev environment — macOS/Linux steps are the same idea, just different
paths/package managers; see the linked official docs for those).

### Installing the Flutter SDK (Windows)

1. Download the latest **stable** Flutter SDK zip from
   [docs.flutter.dev/get-started/install/windows](https://docs.flutter.dev/get-started/install/windows).
2. Extract it somewhere **without spaces or special characters in the path** and
   ideally **not under `C:\Program Files`** (Flutter needs to write to its own
   directory) — e.g. `D:\flutter` or `C:\src\flutter`. This repo's dev environment
   uses `D:\flutter`.
3. Add `<flutter-dir>\bin` to your `PATH` (System Properties → Environment
   Variables → edit the `Path` user/system variable), then open a **new** terminal
   so the change takes effect.
4. Verify:
   ```powershell
   flutter --version
   flutter doctor
   ```
   `flutter doctor` will list what's still missing (Android toolchain, an IDE
   plugin, etc.) — that's expected before the next section.

### Installing the Android SDK (Windows)

You don't need the full Android Studio IDE — the command-line tools are enough to
build and run this app, though Android Studio is the easiest path if you also want
a GUI emulator manager.

**Option A — Android Studio (simplest):**
1. Download and install [Android Studio](https://developer.android.com/studio).
2. On first launch, go through the setup wizard's "Standard" install — it pulls in
   the Android SDK, platform-tools, and a system image for you, by default under
   `%LOCALAPPDATA%\Android\Sdk`.
3. Open **More Actions → SDK Manager** (or **Settings → Languages & Frameworks →
   Android SDK**) and confirm at least one **SDK Platform** (e.g. Android 14/API 34+)
   and, under **SDK Tools**, **Android SDK Build-Tools**, **Android SDK
   Platform-Tools**, and **Android Emulator** are checked/installed.
4. Create a virtual device: **More Actions → Virtual Device Manager → Create
   Device** — pick any Pixel profile and a system image, then finish.

**Option B — command-line tools only (no IDE):**
1. Download the "Command line tools only" zip for Windows from
   [developer.android.com/studio#command-tools](https://developer.android.com/studio#command-tools).
2. Extract it so the folder layout ends up as
   `<sdk-root>\cmdline-tools\latest\bin\...` (the tools expect a `latest` subfolder
   — you'll likely need to create it and move the extracted `cmdline-tools`
   contents into it). This repo's dev environment uses `D:\androidsdk` as
   `<sdk-root>`.
3. Set the `ANDROID_HOME` (and/or `ANDROID_SDK_ROOT`) environment variable to
   `<sdk-root>`, and add both `<sdk-root>\cmdline-tools\latest\bin` and
   `<sdk-root>\platform-tools` to `PATH`.
4. Install the pieces you need and accept the licenses:
   ```powershell
   sdkmanager "platform-tools" "platforms;android-34" "build-tools;34.0.0" "emulator" "system-images;android-34;google_apis;x86_64"
   sdkmanager --licenses
   avdmanager create avd -n Pixel_8 -k "system-images;android-34;google_apis;x86_64" -d pixel_8
   ```

### Connecting the two

Tell Flutter where the Android SDK lives (usually auto-detected if `ANDROID_HOME`
is set, but you can point it explicitly):
```powershell
flutter config --android-sdk "D:\androidsdk"
```

Verify everything end-to-end:
```powershell
flutter doctor -v          # Android toolchain line should show a green checkmark
flutter emulators           # lists AVDs you can launch with `flutter emulators --launch <id>`
flutter emulators --launch Pixel_8
flutter devices             # once the emulator finishes booting, it should appear here
```

If `flutter doctor` flags unaccepted Android licenses, run:
```powershell
flutter doctor --android-licenses
```

## Install dependencies

```bash
cd mobile
flutter pub get
```

## Run it

```bash
flutter run                        # picks a device if only one is connected
flutter run -d <device-id>         # target a specific device/emulator, e.g. emulator-5554
```

Hot reload (`r`) and hot restart (`R`) work as usual once the app is running in an
interactive terminal.

### What base URL to enter on first launch

The app never bakes in a backend URL — you type it into the "Alamat Server" screen
the first time you open the app (persisted locally afterward, changeable later via
the server icon on the login screen). What to enter depends on where you're running
the app from:

| Running on | Base URL to enter |
|---|---|
| Android **emulator**, backend in Docker on the same machine | `http://10.0.2.2:8090` — `10.0.2.2` is the emulator's special alias for the host machine's `localhost`; `http://localhost:8090` will **not** work from inside the emulator |
| A **real Android phone** on the same LAN | `http://<host-machine-LAN-IP>:8090`, e.g. `http://192.168.1.20:8090` |
| Anywhere over the internet | The app's public HTTPS URL (Cloudflare tunnel or real domain — see the root README's "Temporary public domain" section) |

The setup screen validates connectivity (a real request to the backend) before
saving the URL, so a typo or unreachable address is rejected immediately with an
error instead of silently saved.

## Seeded login

Same accounts as the web app — see the root `README.md` for the full list, most
commonly:
```
admin@sebilling.test / password    (super-admin)
```

## Building a release APK

```bash
flutter build apk --release
```
Output: `build/app/outputs/flutter-apk/app-release.apk`. This is unsigned/debug-keyed
by default — set up proper release signing (`android/key.properties` +
`android/app/build.gradle.kts`) before distributing outside your own test devices.

## Known environment gotchas

**Windows: Kotlin incremental-compiler crash if the project and the Pub cache are
on different drive letters.** If `flutter run`/`flutter build apk` fails with a
Gradle/Kotlin daemon error like:
```
IllegalArgumentException: this and base files have different roots: C:\Users\...\Pub\Cache\... and D:\...\mobile\android
```
this is a known Kotlin Gradle plugin bug — `relativeTo()` can't diff paths across
drive roots. Already worked around in this repo via `kotlin.incremental=false` in
`android/gradle.properties`. If you hit it again after a Flutter/Kotlin upgrade,
that's the fix (or move the project onto the same drive as `%LOCALAPPDATA%`/Pub cache).

**Windows Git Bash / MSYS mangles `adb shell input text` arguments containing `/` or
`:`.** Only relevant if you're scripting `adb` calls from Git Bash for testing (not
something end users of the app ever need to do) — prefix the command with
`MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL="*"` to stop MSYS from rewriting the argument
as a Windows path before it reaches `adb.exe`.

**First cold-start render can take 20–30s on a software-rendered/virtualized
emulator GPU.** The Flutter splash logo staying on screen for that long right after
install is normal on some emulator configs (Impeller/OpenGLES context setup is slow
under virtualization) — it's a one-time-per-process-start cost, not a hang.
