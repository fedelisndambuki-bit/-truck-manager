class TripAssignment {
  TripAssignment({
    required this.assignmentId,
    required this.status,
    required this.trip,
  });

  final int assignmentId;
  final String status;
  final Map<String, dynamic> trip;

  factory TripAssignment.fromJson(Map<String, dynamic> json) {
    return TripAssignment(
      assignmentId: json['assignment_id'] as int,
      status: json['status'] as String? ?? 'assigned',
      trip: (json['trip'] as Map<String, dynamic>? ?? {}),
    );
  }
}
