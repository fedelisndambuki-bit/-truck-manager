import 'package:flutter_map/flutter_map.dart';

import '../map/controller/map_controller.dart' show MapController;

/// The result of the `moveAndRotate` [MapController] endpoint.
typedef MoveAndRotateResult = ({bool moveSuccess, bool rotateSuccess});
