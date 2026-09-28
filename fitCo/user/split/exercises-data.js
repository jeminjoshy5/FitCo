// Static exercise + muscle-engagement dataset for the split builder.
// Percentages are illustrative approximations for the distribution
// visual, not lab-measured EMG data.

window.PART_LABELS = {
  chest:      'Chest',
  back:       'Back',
  shoulders:  'Shoulders',
  biceps:     'Biceps',
  triceps:    'Triceps',
  quads:      'Quads',
  hamstrings: 'Hamstrings',
  glutes:     'Glutes',
  calves:     'Calves',
  abs:        'Abs'
};

window.EXERCISES = {

  chest: [
    { name: 'Barbell Bench Press',      engagement: { 'Mid Chest': 55, 'Upper Chest': 15, 'Triceps': 20, 'Front Delts': 10 } },
    { name: 'Incline Barbell Press',    engagement: { 'Upper Chest': 55, 'Mid Chest': 20, 'Front Delts': 15, 'Triceps': 10 } },
    { name: 'Incline Dumbbell Press',   engagement: { 'Upper Chest': 55, 'Mid Chest': 20, 'Front Delts': 15, 'Triceps': 10 } },
    { name: 'Decline Bench Press',      engagement: { 'Lower Chest': 55, 'Mid Chest': 25, 'Triceps': 20 } },
    { name: 'Decline Dumbbell Press',   engagement: { 'Lower Chest': 55, 'Mid Chest': 25, 'Triceps': 20 } },
    { name: 'Dumbbell Bench Press',     engagement: { 'Mid Chest': 55, 'Upper Chest': 15, 'Triceps': 20, 'Front Delts': 10 } },
    { name: 'Dumbbell Flyes',           engagement: { 'Mid Chest': 60, 'Upper Chest': 20, 'Lower Chest': 20 } },
    { name: 'Incline Dumbbell Flyes',   engagement: { 'Upper Chest': 60, 'Mid Chest': 25, 'Front Delts': 15 } },
    { name: 'Cable Crossover',          engagement: { 'Mid Chest': 45, 'Lower Chest': 35, 'Upper Chest': 20 } },
    { name: 'Low-to-High Cable Fly',    engagement: { 'Upper Chest': 60, 'Mid Chest': 25, 'Front Delts': 15 } },
    { name: 'Machine Chest Press',      engagement: { 'Mid Chest': 55, 'Triceps': 25, 'Front Delts': 20 } },
    { name: 'Pec Deck Machine',         engagement: { 'Mid Chest': 70, 'Upper Chest': 30 } },
    { name: 'Push-Up',                  engagement: { 'Mid Chest': 40, 'Triceps': 30, 'Front Delts': 20, 'Core': 10 } },
    { name: 'Incline Push-Up',          engagement: { 'Lower Chest': 40, 'Triceps': 30, 'Front Delts': 30 } },
    { name: 'Weighted Dip (Chest Lean)', engagement: { 'Lower Chest': 45, 'Triceps': 35, 'Front Delts': 20 } },
    { name: 'Svend Press',              engagement: { 'Mid Chest': 65, 'Front Delts': 20, 'Triceps': 15 } }
  ],

  back: [
    { name: 'Pull-Up',                  engagement: { 'Lats': 70, 'Biceps': 20, 'Rear Delts': 10 } },
    { name: 'Chin-Up',                  engagement: { 'Lats': 55, 'Biceps': 35, 'Rear Delts': 10 } },
    { name: 'Lat Pulldown',             engagement: { 'Lats': 65, 'Biceps': 20, 'Mid Back': 15 } },
    { name: 'Close-Grip Lat Pulldown',  engagement: { 'Lats': 60, 'Mid Back': 25, 'Biceps': 15 } },
    { name: 'Straight-Arm Pulldown',    engagement: { 'Lats': 80, 'Triceps': 10, 'Rear Delts': 10 } },
    { name: 'Barbell Row',              engagement: { 'Mid Back': 50, 'Lats': 30, 'Biceps': 20 } },
    { name: 'Pendlay Row',              engagement: { 'Mid Back': 55, 'Lats': 30, 'Biceps': 15 } },
    { name: 'Seated Cable Row',         engagement: { 'Mid Back': 55, 'Lats': 25, 'Biceps': 20 } },
    { name: 'T-Bar Row',                engagement: { 'Mid Back': 60, 'Lats': 25, 'Biceps': 15 } },
    { name: 'Chest-Supported Row',      engagement: { 'Mid Back': 60, 'Lats': 25, 'Rear Delts': 15 } },
    { name: 'Single-Arm Dumbbell Row',  engagement: { 'Lats': 50, 'Mid Back': 30, 'Biceps': 20 } },
    { name: 'Meadows Row',              engagement: { 'Lats': 45, 'Mid Back': 35, 'Biceps': 20 } },
    { name: 'Deadlift',                 engagement: { 'Lower Back': 40, 'Glutes': 25, 'Hamstrings': 20, 'Traps': 15 } },
    { name: 'Rack Pull',                engagement: { 'Traps': 40, 'Lower Back': 35, 'Lats': 25 } },
    { name: 'Face Pull',                engagement: { 'Rear Delts': 55, 'Mid Back': 30, 'Traps': 15 } },
    { name: 'Shrug',                    engagement: { 'Traps': 90, 'Mid Back': 10 } },
    { name: 'Hyperextension',           engagement: { 'Lower Back': 65, 'Glutes': 25, 'Hamstrings': 10 } }
  ],

  shoulders: [
    { name: 'Overhead Press',           engagement: { 'Front Delts': 50, 'Side Delts': 30, 'Triceps': 20 } },
    { name: 'Seated Dumbbell Press',    engagement: { 'Front Delts': 50, 'Side Delts': 30, 'Triceps': 20 } },
    { name: 'Machine Shoulder Press',   engagement: { 'Front Delts': 50, 'Side Delts': 30, 'Triceps': 20 } },
    { name: 'Arnold Press',             engagement: { 'Front Delts': 45, 'Side Delts': 40, 'Triceps': 15 } },
    { name: 'Push Press',               engagement: { 'Front Delts': 45, 'Side Delts': 30, 'Triceps': 15, 'Traps': 10 } },
    { name: 'Lateral Raise',            engagement: { 'Side Delts': 90, 'Front Delts': 10 } },
    { name: 'Cable Lateral Raise',      engagement: { 'Side Delts': 90, 'Front Delts': 10 } },
    { name: 'Leaning Lateral Raise',    engagement: { 'Side Delts': 95, 'Traps': 5 } },
    { name: 'Front Raise',              engagement: { 'Front Delts': 90, 'Side Delts': 10 } },
    { name: 'Plate Front Raise',        engagement: { 'Front Delts': 90, 'Side Delts': 10 } },
    { name: 'Rear Delt Fly',            engagement: { 'Rear Delts': 85, 'Traps': 15 } },
    { name: 'Reverse Pec Deck',         engagement: { 'Rear Delts': 85, 'Traps': 15 } },
    { name: 'Cable Rear Delt Fly',      engagement: { 'Rear Delts': 85, 'Traps': 15 } },
    { name: 'Upright Row',              engagement: { 'Side Delts': 50, 'Traps': 35, 'Front Delts': 15 } },
    { name: 'Cable Y-Raise',            engagement: { 'Side Delts': 55, 'Front Delts': 30, 'Traps': 15 } }
  ],

  biceps: [
    { name: 'Barbell Curl',             engagement: { 'Biceps (Long Head)': 50, 'Biceps (Short Head)': 40, 'Forearms': 10 } },
    { name: 'EZ-Bar Curl',              engagement: { 'Biceps (Long Head)': 50, 'Biceps (Short Head)': 40, 'Forearms': 10 } },
    { name: 'Dumbbell Curl',            engagement: { 'Biceps (Long Head)': 50, 'Biceps (Short Head)': 40, 'Forearms': 10 } },
    { name: 'Dumbbell Hammer Curl',     engagement: { 'Brachialis': 55, 'Forearms': 30, 'Biceps': 15 } },
    { name: 'Cross-Body Hammer Curl',   engagement: { 'Brachialis': 55, 'Forearms': 30, 'Biceps': 15 } },
    { name: 'Incline Dumbbell Curl',    engagement: { 'Biceps (Long Head)': 65, 'Biceps (Short Head)': 25, 'Forearms': 10 } },
    { name: 'Preacher Curl',            engagement: { 'Biceps (Short Head)': 60, 'Biceps (Long Head)': 30, 'Forearms': 10 } },
    { name: 'Concentration Curl',       engagement: { 'Biceps (Short Head)': 55, 'Biceps (Long Head)': 35, 'Forearms': 10 } },
    { name: 'Cable Curl',               engagement: { 'Biceps (Short Head)': 50, 'Biceps (Long Head)': 40, 'Forearms': 10 } },
    { name: 'Spider Curl',              engagement: { 'Biceps (Short Head)': 65, 'Biceps (Long Head)': 25, 'Forearms': 10 } },
    { name: 'Reverse Curl',             engagement: { 'Forearms': 55, 'Brachialis': 35, 'Biceps': 10 } },
    { name: '21s',                      engagement: { 'Biceps (Long Head)': 45, 'Biceps (Short Head)': 45, 'Forearms': 10 } }
  ],

  triceps: [
    { name: 'Close-Grip Bench Press',        engagement: { 'Triceps (Lateral Head)': 40, 'Triceps (Long Head)': 35, 'Chest': 25 } },
    { name: 'Triceps Pushdown',              engagement: { 'Triceps (Lateral Head)': 55, 'Triceps (Medial Head)': 35, 'Triceps (Long Head)': 10 } },
    { name: 'Rope Pushdown',                 engagement: { 'Triceps (Lateral Head)': 50, 'Triceps (Medial Head)': 40, 'Triceps (Long Head)': 10 } },
    { name: 'Overhead Rope Extension',       engagement: { 'Triceps (Long Head)': 65, 'Triceps (Lateral Head)': 25, 'Triceps (Medial Head)': 10 } },
    { name: 'Skull Crushers',                engagement: { 'Triceps (Long Head)': 55, 'Triceps (Lateral Head)': 35, 'Triceps (Medial Head)': 10 } },
    { name: 'Dumbbell Skull Crushers',       engagement: { 'Triceps (Long Head)': 55, 'Triceps (Lateral Head)': 35, 'Triceps (Medial Head)': 10 } },
    { name: 'Overhead Dumbbell Extension',   engagement: { 'Triceps (Long Head)': 65, 'Triceps (Lateral Head)': 25, 'Triceps (Medial Head)': 10 } },
    { name: 'Single-Arm Overhead Extension', engagement: { 'Triceps (Long Head)': 70, 'Triceps (Lateral Head)': 20, 'Triceps (Medial Head)': 10 } },
    { name: 'Dips',                          engagement: { 'Triceps (Long Head)': 40, 'Chest': 35, 'Front Delts': 25 } },
    { name: 'Bench Dips',                    engagement: { 'Triceps (Lateral Head)': 50, 'Triceps (Long Head)': 30, 'Front Delts': 20 } },
    { name: 'Diamond Push-Up',               engagement: { 'Triceps (Lateral Head)': 45, 'Chest': 35, 'Front Delts': 20 } },
    { name: 'Kickback',                      engagement: { 'Triceps (Lateral Head)': 60, 'Triceps (Long Head)': 30, 'Triceps (Medial Head)': 10 } }
  ],

  quads: [
    { name: 'Barbell Back Squat',       engagement: { 'Quads': 50, 'Glutes': 30, 'Hamstrings': 20 } },
    { name: 'Front Squat',              engagement: { 'Quads': 60, 'Glutes': 25, 'Core': 15 } },
    { name: 'Goblet Squat',             engagement: { 'Quads': 55, 'Glutes': 30, 'Core': 15 } },
    { name: 'Hack Squat',               engagement: { 'Quads': 65, 'Glutes': 25, 'Hamstrings': 10 } },
    { name: 'Leg Press',                engagement: { 'Quads': 55, 'Glutes': 30, 'Hamstrings': 15 } },
    { name: 'Leg Extension',            engagement: { 'Quads': 90, 'Hip Flexors': 10 } },
    { name: 'Bulgarian Split Squat',    engagement: { 'Quads': 45, 'Glutes': 40, 'Hamstrings': 15 } },
    { name: 'Walking Lunge',            engagement: { 'Quads': 45, 'Glutes': 40, 'Hamstrings': 15 } },
    { name: 'Reverse Lunge',            engagement: { 'Quads': 45, 'Glutes': 40, 'Hamstrings': 15 } },
    { name: 'Step-Up',                  engagement: { 'Quads': 50, 'Glutes': 40, 'Hamstrings': 10 } },
    { name: 'Sissy Squat',              engagement: { 'Quads': 90, 'Core': 10 } },
    { name: 'Smith Machine Squat',      engagement: { 'Quads': 55, 'Glutes': 30, 'Hamstrings': 15 } }
  ],

  hamstrings: [
    { name: 'Romanian Deadlift',        engagement: { 'Hamstrings': 55, 'Glutes': 35, 'Lower Back': 10 } },
    { name: 'Dumbbell RDL',             engagement: { 'Hamstrings': 55, 'Glutes': 35, 'Lower Back': 10 } },
    { name: 'Stiff-Leg Deadlift',       engagement: { 'Hamstrings': 60, 'Glutes': 25, 'Lower Back': 15 } },
    { name: 'Leg Curl (Lying)',         engagement: { 'Hamstrings': 90, 'Calves': 10 } },
    { name: 'Leg Curl (Seated)',        engagement: { 'Hamstrings': 90, 'Calves': 10 } },
    { name: 'Nordic Curl',              engagement: { 'Hamstrings': 85, 'Glutes': 15 } },
    { name: 'Good Morning',             engagement: { 'Hamstrings': 50, 'Lower Back': 30, 'Glutes': 20 } },
    { name: 'Glute-Ham Raise',          engagement: { 'Hamstrings': 60, 'Glutes': 30, 'Lower Back': 10 } },
    { name: 'Single-Leg RDL',           engagement: { 'Hamstrings': 55, 'Glutes': 35, 'Core': 10 } },
    { name: 'Cable Pull-Through',       engagement: { 'Hamstrings': 45, 'Glutes': 45, 'Lower Back': 10 } }
  ],

  glutes: [
    { name: 'Hip Thrust',               engagement: { 'Glutes': 75, 'Hamstrings': 25 } },
    { name: 'Barbell Glute Bridge',     engagement: { 'Glutes': 70, 'Hamstrings': 30 } },
    { name: 'Single-Leg Hip Thrust',    engagement: { 'Glutes': 80, 'Hamstrings': 20 } },
    { name: 'Cable Kickback',           engagement: { 'Glutes': 85, 'Hamstrings': 15 } },
    { name: 'Sumo Deadlift',            engagement: { 'Glutes': 45, 'Hamstrings': 30, 'Quads': 25 } },
    { name: 'Curtsy Lunge',             engagement: { 'Glutes': 55, 'Quads': 30, 'Hamstrings': 15 } },
    { name: 'Hip Abduction Machine',    engagement: { 'Glutes': 90, 'Hip Flexors': 10 } },
    { name: 'Frog Pump',                engagement: { 'Glutes': 85, 'Hamstrings': 15 } },
    { name: 'Banded Lateral Walk',      engagement: { 'Glutes': 90, 'Hip Flexors': 10 } },
    { name: 'Step-Down',                engagement: { 'Glutes': 55, 'Quads': 35, 'Hamstrings': 10 } }
  ],

  calves: [
    { name: 'Standing Calf Raise',      engagement: { 'Gastrocnemius': 80, 'Soleus': 20 } },
    { name: 'Seated Calf Raise',        engagement: { 'Soleus': 85, 'Gastrocnemius': 15 } },
    { name: 'Leg Press Calf Raise',     engagement: { 'Gastrocnemius': 60, 'Soleus': 40 } },
    { name: 'Smith Machine Calf Raise', engagement: { 'Gastrocnemius': 75, 'Soleus': 25 } },
    { name: 'Single-Leg Calf Raise',    engagement: { 'Gastrocnemius': 75, 'Soleus': 25 } },
    { name: 'Donkey Calf Raise',        engagement: { 'Gastrocnemius': 80, 'Soleus': 20 } },
    { name: 'Jump Rope',                engagement: { 'Gastrocnemius': 60, 'Soleus': 30, 'Core': 10 } }
  ],

  abs: [
    { name: 'Hanging Leg Raise',        engagement: { 'Lower Abs': 70, 'Hip Flexors': 30 } },
    { name: 'Hanging Knee Raise',       engagement: { 'Lower Abs': 65, 'Hip Flexors': 35 } },
    { name: 'Cable Crunch',             engagement: { 'Upper Abs': 75, 'Obliques': 25 } },
    { name: 'Machine Crunch',           engagement: { 'Upper Abs': 80, 'Obliques': 20 } },
    { name: 'Sit-Up',                   engagement: { 'Upper Abs': 55, 'Hip Flexors': 30, 'Lower Abs': 15 } },
    { name: 'Crunch',                   engagement: { 'Upper Abs': 85, 'Obliques': 15 } },
    { name: 'Plank',                    engagement: { 'Core Stability': 60, 'Obliques': 40 } },
    { name: 'Side Plank',               engagement: { 'Obliques': 80, 'Core Stability': 20 } },
    { name: 'Russian Twist',            engagement: { 'Obliques': 80, 'Upper Abs': 20 } },
    { name: 'Ab Wheel Rollout',         engagement: { 'Upper Abs': 45, 'Lower Abs': 35, 'Obliques': 20 } },
    { name: 'Mountain Climber',         engagement: { 'Lower Abs': 45, 'Hip Flexors': 35, 'Core Stability': 20 } },
    { name: 'V-Up',                     engagement: { 'Upper Abs': 45, 'Lower Abs': 45, 'Hip Flexors': 10 } },
    { name: 'Reverse Crunch',           engagement: { 'Lower Abs': 75, 'Hip Flexors': 25 } },
    { name: 'Bicycle Crunch',           engagement: { 'Obliques': 55, 'Upper Abs': 35, 'Lower Abs': 10 } }
  ]

};

window.SPLIT_DEFS = {
  ppl: [
    { day: 'Push Day', parts: ['chest', 'shoulders', 'triceps'] },
    { day: 'Pull Day', parts: ['back', 'biceps'] },
    { day: 'Leg Day',  parts: ['quads', 'hamstrings', 'glutes', 'calves'] }
  ],
  bro: [
    { day: 'Chest Day',    parts: ['chest'] },
    { day: 'Back Day',     parts: ['back'] },
    { day: 'Shoulder Day', parts: ['shoulders'] },
    { day: 'Arm Day',      parts: ['biceps', 'triceps'] },
    { day: 'Leg Day',      parts: ['quads', 'hamstrings', 'glutes', 'calves'] },
    { day: 'Core Day',     parts: ['abs'] }
  ]
};
