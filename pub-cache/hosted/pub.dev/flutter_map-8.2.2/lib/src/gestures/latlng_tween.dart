import 'package:flutter/animation.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../../../../latlong2-0.9.1/lib/latlong.dart' show LatLng;

import '../geo/latlng_bounds.dart' show LatLngBounds;

import '../map/camera/camera.dart' show MapCamera;

/// A [Tween] object for [LatLng]. Used for [AnimationController] to handle
/// animated changes of the [MapCamera].
class LatLngTween extends Tween<LatLng> {
  /// Create a new [LatLngBounds] object by providing the [begin] and [end]
  /// coordinates.
  LatLngTween({required LatLng begin, required LatLng end})
      : super(begin: begin, end: end);

  @override
  LatLng lerp(double t) => LatLng(
        begin!.latitude + (end!.latitude - begin!.latitude) * t,
        begin!.longitude + (end!.longitude - begin!.longitude) * t,
      );
}
