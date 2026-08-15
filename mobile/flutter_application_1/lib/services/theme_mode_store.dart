import 'package:flutter/material.dart';

class ThemeModeStore {
  ThemeModeStore._();

  static final ValueNotifier<ThemeMode> mode = ValueNotifier(ThemeMode.light);

  static void setLight() => mode.value = ThemeMode.light;
  static void setDark() => mode.value = ThemeMode.dark;
}
