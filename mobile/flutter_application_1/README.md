# Truck Manager Driver App

## API URL Configuration

The app reads backend origin from `API_BASE_URL` and appends `/api/driver` automatically.

Example values:
- `http://192.168.1.50:8000`
- `http://10.0.2.2:8000` (Android emulator)

Run examples:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.50:8000
```

```bash
flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000
```

If you pass `.../api` or `.../api/driver`, the app handles both forms.
