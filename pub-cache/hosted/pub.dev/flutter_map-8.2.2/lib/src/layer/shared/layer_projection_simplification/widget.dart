import 'package:flutter/material.dart';
import 'package:flutter_map/src/layer/shared/layer_projection_simplification/state.dart';

import '../../../../../../test_api-0.7.9/lib/hooks_testing.dart' show State;

import '../../../../../../test_api-0.7.9/lib/src/backend/state.dart' show State;

import 'state.dart' show ProjectionSimplificationManagement;

/// A [StatefulWidget] that includes the properties used by the [State] component
/// which mixes [ProjectionSimplificationManagement] in
@immutable
abstract base class ProjectionSimplificationManagementSupportedWidget
    extends StatefulWidget {
  /// Distance between two neighboring polyline points, in logical pixels scaled
  /// to floored zoom
  ///
  /// Increasing this value results in points further apart being collapsed and
  /// thus more simplified polylines. Higher values improve performance at the
  /// cost of visual fidelity and vice versa.
  ///
  /// Defaults to 0.3. Set to 0 to disable simplification.
  final double simplificationTolerance;

  /// A [StatefulWidget] that includes the properties used by the [State]
  /// component which mixes [ProjectionSimplificationManagement] in
  ///
  /// Constructors should call `super()` (the super constructor) to ensure the
  /// necessary assertions are made.
  const ProjectionSimplificationManagementSupportedWidget({
    super.key,
    this.simplificationTolerance = 0.3,
  }) : assert(
          simplificationTolerance >= 0,
          'simplificationTolerance cannot be negative: $simplificationTolerance',
        );
}
