from dataset.main import data, data_for_subject_dependet
import torch
import os
from models_structures.ACRNN import ACRNN
from train import Trainer
import random
from functions import k_fold_data_segmentation
from torch.utils.data import DataLoader, TensorDataset
import numpy as np


#____Model______#
def create_model(test_person, emotion, category, fold_idx, run_dir=None, config_path=None, config=None, resume=False):
    from pathlib import Path
    
    overlap = 0
    time_len = 1
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    
    if category == 'binary':
        output_dim = 2
    elif category == '5category':
        output_dim = 5
    
    batch_size = 10  # As in original ACRNN paper
    data_type = torch.float32
    
    my_dataset = data(test_person, overlap, time_len, device, emotion, category, batch_size, data_type)
    train_loader = my_dataset.train_data()
    test_loader = my_dataset.test_data()
    
    # Get input dimensions from dataset
    # Assuming data shape is (batch, time_len, channels)
    # We need to reshape to (batch, channels, time_len, 1) for ACRNN
    sample_x, _ = next(iter(train_loader))
    if sample_x.dim() == 3:
        # Shape: (batch, time_len, channels)
        _, time_steps, n_channels = sample_x.shape
        input_height = n_channels  # Number of EEG channels
        input_width = time_steps    # Time window length
    else:
        # Default values (DEAP dataset: 32 channels, 384 time steps)
        input_height = 32
        input_width = 384
    
    # Create ACRNN model with parameters from paper
    Model = ACRNN(
        input_height=input_height,      # Number of EEG channels
        input_width=input_width,        # Time window length
        input_channels=1,              # Input channels
        conv_channels=40,               # Number of CNN filters
        kernel_height=input_height,     # CNN kernel height (full channel dimension)
        kernel_width=45,                # CNN kernel width
        pool_height=1,                 # Pooling height
        pool_width=75,                  # Pooling width
        pool_stride=10,                 # Pooling stride
        lstm_hidden=64,                # LSTM hidden size
        num_lstm_layers=2,            # Number of LSTM layers
        num_classes=output_dim,        # Output classes
        dropout=0.5                    # Dropout probability
    )
    
    # Class weights for imbalance
    y_train = my_dataset.y_train
    class_count = torch.bincount(y_train.long())
    class_count = class_count + (class_count == 0).long()  # avoid zero
    weights = (1.0 / class_count.float())
    weights = weights / weights.sum() * len(class_count)
    
    # Determine checkpoint and log paths
    if run_dir:
        run_dir = Path(run_dir)
        checkpoint_path = run_dir / f"checkpoint_fold{fold_idx}.pth"
        log_path = run_dir / f"log_fold{fold_idx}.json"
    else:
        checkpoint_path = f"eeg_checkpoint{fold_idx}.pth"
        log_path = f"eeg_log{fold_idx}.json"
    
    # Create a wrapper to handle input reshaping
    class ModelWrapper(torch.nn.Module):
        def __init__(self, model, input_height, input_width):
            super().__init__()
            self.model = model
            self.input_height = input_height
            self.input_width = input_width
        
        def forward(self, x):
            # Reshape input from (batch, time_len, channels) to (batch, channels, time_len, 1)
            if x.dim() == 3:
                # x: (batch, time_len, channels)
                x = x.permute(0, 2, 1)  # (batch, channels, time_len)
                x = x.unsqueeze(-1)     # (batch, channels, time_len, 1)
            return self.model(x)
    
    wrapped_model = ModelWrapper(Model, input_height, input_width)
    
    #____trainer_______#
    trainer = Trainer(
        model=wrapped_model,
        train_loader=train_loader,
        test_loader=test_loader,
        device=device,
        label_method=category,
        optimizer_cls=torch.optim.Adam,
        lr=1e-4,  # Learning rate from paper
        epochs=30,  # Training epochs from paper
        loss_fn=torch.nn.CrossEntropyLoss(weight=weights.to(device)),
        checkpoint_path=str(checkpoint_path),
        log_path=str(log_path),
        config_path=config_path,
        save_each_epoch=True
    )
    #____fit_model_____#
    return trainer.fit()


def subject_dependent_validation(emotion, category, fold_idx, k=5, run_dir=None, config_path=None, config=None, resume=False):
    from pathlib import Path
    from experiment_manager import ExperimentManager
    
    overlap = 0.05
    time_len = 1
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    
    if category == 'binary':
        output_dim = 2
    elif category == '5category':
        output_dim = 5
    
    batch_size = 10  # As in original ACRNN paper
    data_type = torch.float32
    
    # Load previous results if resume=True
    accuracies_on_subjects = {
        'train': [],
        'test': []
    }
    start_subject = 0
    start_fold = 0
    current_subject = -1
    
    if resume and config and config_path:
        manager = ExperimentManager()
        # Read last completed subject and fold
        last_subject = config.get('last_completed_subject', -1)
        last_fold = config.get('last_completed_fold', -1)
        current_subject = config.get('current_subject', -1)
        
        # If previous subject is complete, start from next subject
        if last_fold == k - 1:  # All folds of previous subject completed
            start_subject = last_subject + 1
            start_fold = 0
        else:  # Previous subject is incomplete
            start_subject = current_subject
            start_fold = last_fold + 1
        
        # Load previous results
        if 'accuracies' in config:
            accuracies_on_subjects['train'] = config['accuracies'].get('train', [])
            accuracies_on_subjects['test'] = config['accuracies'].get('test', [])
        
        print(f"\n🔄 Resuming from Subject {start_subject}, Fold {start_fold + 1} (previous subjects: {len(accuracies_on_subjects['train'])} completed)")
    
    person_num = start_subject
    data = data_for_subject_dependet(overlap, time_len, emotion, category, data_type, device, k)
    
    # Convert iterator to list for skipping capability
    data_list = list(data.data)
    
    # Start from specified subject
    for subject_idx, (x, y) in enumerate(data_list[start_subject:], start=start_subject):
        # Update current_subject in config
        if config_path and run_dir:
            manager = ExperimentManager()
            manager.update_experiment_config(
                config_path,
                current_subject=person_num
            )
        
        # If new subject, start from fold 0, otherwise from specified fold
        if subject_idx == start_subject:
            fold_start = start_fold
        else:
            fold_start = 0
        
        fold_idx = fold_start
        len_data = x.shape[0]
        fold_number = len_data // k
        all_x = [x[fold_number*i: min(fold_number*(i+1), len_data), :, :] for i in range(k)]
        all_y = [y[fold_number*i: min(fold_number*(i+1), len_data)] for i in range(k)]
        
        print("\n" + "="*60)
        print(f"Subject {person_num}: Training {k}-fold cross-validation")
        print("="*60)
        
        # Get input dimensions from first sample
        if len(all_x[0]) > 0:
            sample_x = all_x[0][0:1]
            if sample_x.dim() == 3:
                _, time_steps, n_channels = sample_x.shape
                input_height = n_channels
                input_width = time_steps
            else:
                input_height = 32
                input_width = 384
        else:
            input_height = 32
            input_width = 384
        
        for i in range(fold_start, k):
            print(f"\n-- Fold {i+1}/{k} --")
            x_test = all_x[i]
            y_test = all_y[i]
            x_train = all_x[:i] + all_x[i+1:]
            y_train = all_y[:i] + all_y[i+1:]
            x_train = torch.concat(x_train, dim=0)
            y_train = torch.concat(y_train, dim=0)
            
            test_dataset = TensorDataset(x_test, y_test)
            test_loader = DataLoader(test_dataset, batch_size, shuffle=False)
            train_dataset = TensorDataset(x_train, y_train)
            train_loader = DataLoader(train_dataset, batch_size, shuffle=True)
            
            # Create ACRNN model
            Model = ACRNN(
                input_height=input_height,
                input_width=input_width,
                input_channels=1,
                conv_channels=40,
                kernel_height=input_height,
                kernel_width=45,
                pool_height=1,
                pool_width=75,
                pool_stride=10,
                lstm_hidden=64,
                num_lstm_layers=2,
                num_classes=output_dim,
                dropout=0.5
            )
            
            # Create wrapper for input reshaping
            class ModelWrapper(torch.nn.Module):
                def __init__(self, model, input_height, input_width):
                    super().__init__()
                    self.model = model
                    self.input_height = input_height
                    self.input_width = input_width
                
                def forward(self, x):
                    if x.dim() == 3:
                        x = x.permute(0, 2, 1)
                        x = x.unsqueeze(-1)
                    return self.model(x)
            
            wrapped_model = ModelWrapper(Model, input_height, input_width)
            
            # Determine checkpoint and log paths
            if run_dir:
                run_dir = Path(run_dir)
                subject_dir = run_dir / f"subject_{person_num}"
                subject_dir.mkdir(exist_ok=True)
                checkpoint_path = subject_dir / f"checkpoint_fold{i}.pth"
                log_path = subject_dir / f"log_fold{i}.json"
            else:
                checkpoint_path = f"eeg_checkpoint{fold_idx + person_num*5}.pth"
                log_path = f"eeg_log{fold_idx + person_num*5}.json"
            
            #____trainer_______#
            trainer = Trainer(
                model=wrapped_model,
                train_loader=train_loader,
                test_loader=test_loader,
                device=device,
                label_method=category,
                optimizer_cls=torch.optim.Adam,
                lr=1e-4,
                epochs=30,
                verbose=True,
                save_each_epoch=True,
                checkpoint_path=str(checkpoint_path),
                log_path=str(log_path),
                config_path=None
            )
            #____fit_model_____#
            history = trainer.fit()
            
            # Save history to JSON file (for plotting)
            import json
            history_to_save = {
                'epoch': history['epoch'],
                'train_loss': [float(x) for x in history['train_loss']],
                'val_loss': [float(x) for x in history['val_loss']],
                'train_acc': [float(x) for x in history['train_acc']],
                'val_acc': [float(x) for x in history['val_acc']]
            }
            with open(log_path, 'w') as f:
                json.dump(history_to_save, f, indent=4)
            
            # Plot and save graphs for this fold
            from plot import plot_training_history
            plot_training_history(history_to_save, save_dir=subject_dir, filename_prefix=f"fold_{i}")
            
            fold_train_acc = np.mean(np.array(history['train_acc'][-5:]))
            fold_val_acc = np.mean(np.array(history['val_acc'][-5:]))
            print(f"Fold {i+1} result -> Train Acc (last5 avg): {fold_train_acc:.2f}% | Test Acc (last5 avg): {fold_val_acc:.2f}%")
            
            if fold_idx == 0:
                train_loss = np.array(history['train_loss'])
                val_loss = np.array(history['val_loss'])
                train_acc = np.array(history['train_acc'])
                val_acc = np.array(history['val_acc'])
            else:
                train_loss += np.array(history['train_loss'])
                val_loss += np.array(history['val_loss'])
                train_acc += np.array(history['train_acc'])
                val_acc += np.array(history['val_acc'])
            
            # Update config after each fold
            if config_path and run_dir:
                manager = ExperimentManager()
                manager.update_experiment_config(
                    config_path,
                    last_completed_fold=i,
                    current_subject=person_num
                )
            
            fold_idx += 1
        
        # After completing all folds of one subject
        train_acc /= k
        train_loss /= k
        val_loss /= k
        val_acc /= k
        
        accuracies_on_subjects['train'].append(np.mean(np.array(train_acc[-5:])))
        accuracies_on_subjects['test'].append(np.mean(np.array(val_acc[-5:])))
        
        # Update config after completing subject
        if config_path and run_dir:
            manager = ExperimentManager()
            manager.update_experiment_config(
                config_path,
                last_completed_subject=person_num,
                last_completed_fold=k-1,
                accuracies={
                    'train': accuracies_on_subjects['train'],
                    'test': accuracies_on_subjects['test']
                }
            )
            print(f"✅ Subject {person_num} completed and saved to config")
        
        person_num += 1
    
    return accuracies_on_subjects

