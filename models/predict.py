# import os
# import sys
# import json
# import numpy as np
# import tensorflow as tf
# import keras 

# # --- CRITICAL PATCH: Must be before load_model ---
# from keras.src.layers.core.dense import Dense
# original_init = Dense.__init__
# def patched_init(self, *args, **kwargs):
#     kwargs.pop('quantization_config', None)
#     original_init(self, *args, **kwargs)
# Dense.__init__ = patched_init
# # -----------------------------------------------

# # Silence TensorFlow logs
# os.environ['CUDA_VISIBLE_DEVICES'] = '-1'
# os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

# BASE_DIR = os.path.dirname(os.path.abspath(__file__))
# MODEL_TYPE_PATH = os.path.join(BASE_DIR, 'skinmate_type_best_v2.keras')
# MODEL_COND_PATH = os.path.join(BASE_DIR, 'skinmate_conditions_best_v1.keras')

# TYPE_LABELS = ['Combination', 'Dry', 'Normal', 'Oily']
# COND_LABELS = ['Acne', 'Dark Spots', 'Pigmentation', 'Pores', 'Redness', 'Wrinkles']

# def get_full_analysis(img_path):
#     if not os.path.exists(img_path):
#         return json.dumps({"status": "error", "message": "Image not found"})

#     try:
#         tf.config.run_functions_eagerly(True)

#         with tf.device('/CPU:0'):
#             # --- MODEL 1: SKIN TYPE (224x224) ---
#             model_type = keras.models.load_model(MODEL_TYPE_PATH, compile=False)
#             img_type = keras.utils.load_img(img_path, target_size=(224, 224))
#             arr_type = keras.utils.img_to_array(img_type) / 255.0
#             arr_type = np.expand_dims(arr_type, axis=0)
            
#             type_preds = model_type(arr_type, training=False).numpy()
#             skin_type = TYPE_LABELS[np.argmax(type_preds[0])]

#             # --- MODEL 2: CONDITIONS (300x300) ---
#             model_cond = keras.models.load_model(MODEL_COND_PATH, compile=False)
#             img_cond = keras.utils.load_img(img_path, target_size=(300, 300))
#             arr_cond = keras.utils.img_to_array(img_cond) / 255.0
#             arr_cond = np.expand_dims(arr_cond, axis=0)
            
#             cond_preds = model_cond(arr_cond, training=False).numpy()[0]
            
#             # Map probabilities
#             # conditions_map = {COND_LABELS[i]: float(cond_preds[i]) for i in range(len(COND_LABELS))}
#             # conditions_map = {
#             #     COND_LABELS[i]: round((1.0 - float(cond_preds[i])) * 100, 1) 
#             #     for i in range(len(COND_LABELS))
#             # }

#             # --- CALCULATE HEALTH SCORE ---
#             # Average the presence of all conditions. 
#             # 1.0 (perfect) - avg_score = health %
#             # avg_presence = sum(conditions_map.values()) / len(conditions_map)
#             # health_score = int(max(0, (1.0 - avg_presence)) * 100)
#             # health_score = int(sum(conditions_map.values()) / len(conditions_map))

#             # 1. Flip probabilities to "Clarity Marks" (0.1 prob -> 90.0 mark)
#             conditions_map = {}
#             for i in range(len(COND_LABELS)):
#                 prob = float(cond_preds[i])
#                 mark = round((1.0 - prob) * 100, 1)
#                 conditions_map[COND_LABELS[i]] = mark

#             # 2. Calculate average score safely
#             if conditions_map:
#                 total_marks = sum(conditions_map.values())
#                 health_score = int(total_marks / len(conditions_map))
#             else:
#                 health_score = 0

#             # 3. Final clamp to stay within 0-100
#             health_score = max(0, min(100, health_score))

#             return json.dumps({
#                 "status": "success",
#                 "skin_type": skin_type,
#                 "health_score": health_score,
#                 "conditions": conditions_map
#             })
        
#     except Exception as e:
#         return json.dumps({"status": "error", "message": str(e)})

# if __name__ == "__main__":
#     if len(sys.argv) < 2:
#         print(json.dumps({"status": "error", "message": "No path provided"}))
#     else:
#         # Redirect stderr to null so PHP only catches the JSON string
#         sys.stderr = open(os.devnull, 'w')
#         print(get_full_analysis(sys.argv[1]))

import os
import sys
import json
import cv2
import numpy as np
import tensorflow as tf
import keras 

# --- CRITICAL PATCH: Must be before load_model ---
from keras.src.layers.core.dense import Dense
original_init = Dense.__init__
def patched_init(self, *args, **kwargs):
    kwargs.pop('quantization_config', None)
    original_init(self, *args, **kwargs)
Dense.__init__ = patched_init
# -----------------------------------------------

# Silence TensorFlow logs
os.environ['CUDA_VISIBLE_DEVICES'] = '-1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_TYPE_PATH = os.path.join(BASE_DIR, 'skinmate_type_best_v2.keras')
MODEL_COND_PATH = os.path.join(BASE_DIR, 'skinmate_conditions_best_v1.keras')

TYPE_LABELS = ['Combination', 'Dry', 'Normal', 'Oily']
COND_LABELS = ['Acne', 'Dark Spots', 'Pigmentation', 'Pores', 'Redness', 'Wrinkles']

def get_full_analysis(img_path):
    if not os.path.exists(img_path):
        return json.dumps({"status": "error", "message": "Image not found"})

    # --- 🛡️ STEP 1: FACE DETECTION VALIDATION GUARD ---
    try:
        img_check = cv2.imread(img_path)
        if img_check is None:
            return json.dumps({"status": "error", "message": "Failed to decode or read image file"})
            
        # Initialize Haar Cascade frontal face model
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        face_cascade = cv2.CascadeClassifier(cascade_path)
        
        # Performance tuning parameters: Convert to grayscale for quick matrix comparison
        gray = cv2.cvtColor(img_check, cv2.COLOR_BGR2GRAY)
        faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=4, minSize=(100, 100))
        
        # 🛑 REJECT if no faces are captured in the photo frame bounds
        if len(faces) == 0:
            return json.dumps({
                "status": "error", 
                "message": "No human face detected in the uploaded image."
            })
    except Exception as e:
        return json.dumps({"status": "error", "message": f"Face validation failure component error: {str(e)}"})

    # --- STEP 2: PROCEED WITH DEEP LEARNING MODEL INFERENCES ---
    try:
        tf.config.run_functions_eagerly(True)

        with tf.device('/CPU:0'):
            # --- MODEL 1: SKIN TYPE (224x224) ---
            model_type = keras.models.load_model(MODEL_TYPE_PATH, compile=False)
            img_type = keras.utils.load_img(img_path, target_size=(224, 224))
            arr_type = keras.utils.img_to_array(img_type) / 255.0
            arr_type = np.expand_dims(arr_type, axis=0)
            
            type_preds = model_type(arr_type, training=False).numpy()
            skin_type = TYPE_LABELS[np.argmax(type_preds[0])]

            # --- MODEL 2: CONDITIONS (300x300) ---
            model_cond = keras.models.load_model(MODEL_COND_PATH, compile=False)
            img_cond = keras.utils.load_img(img_path, target_size=(300, 300))
            arr_cond = keras.utils.img_to_array(img_cond) / 255.0
            arr_cond = np.expand_dims(arr_cond, axis=0)
            
            cond_preds = model_cond(arr_cond, training=False).numpy()[0]
            
            # 1. Flip probabilities to "Clarity Marks" (0.1 prob -> 90.0 mark)
            conditions_map = {}
            for i in range(len(COND_LABELS)):
                prob = float(cond_preds[i])
                mark = round((1.0 - prob) * 100, 1)
                conditions_map[COND_LABELS[i]] = mark

            # 2. Calculate average score safely
            if conditions_map:
                total_marks = sum(conditions_map.values())
                health_score = int(total_marks / len(conditions_map))
            else:
                health_score = 0

            # 3. Final clamp to stay within 0-100
            health_score = max(0, min(100, health_score))

            return json.dumps({
                "status": "success",
                "skin_type": skin_type,
                "health_score": health_score,
                "conditions": conditions_map
            })
        
    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No path provided"}))
    else:
        # Redirect stderr to null so PHP only catches the JSON string
        sys.stderr = open(os.devnull, 'w')
        print(get_full_analysis(sys.argv[1]))