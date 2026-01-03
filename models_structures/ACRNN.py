"""
ACRNN: Attention-based CNN-RNN for EEG Emotion Recognition
Architecture from: EEG-based Emotion Recognition via Channel-wise Attention and Self Attention
Converted from TensorFlow to PyTorch
"""
import torch
import torch.nn as nn
import torch.nn.functional as F


class ChannelWiseAttention(nn.Module):
    """
    Channel-wise Attention mechanism - Exact conversion from TensorFlow
    Original: channel_wise_attention(feature_map, H, W, C, ...)
    Input format: (batch, H, W, C) - TensorFlow BHWC format
    """
    def __init__(self, H, W, C, weight_decay=0.00004):
        super(ChannelWiseAttention, self).__init__()
        self.H = H
        self.W = W
        self.C = C
        
        # Weight matrix: C x C
        self.weight = nn.Parameter(torch.empty(C, C))
        self.bias = nn.Parameter(torch.zeros(C))
        
        # Initialize with orthogonal weights
        nn.init.orthogonal_(self.weight)
        
    def forward(self, feature_map):
        """
        Args:
            feature_map: (batch, H, W, C) - TensorFlow BHWC format
        Returns:
            attended_fm: (batch, H, W, C) - same shape as input
        """
        batch_size = feature_map.shape[0]
        
        # Step 1: reduce_mean over dimensions [1, 2] (H and W), keep_dims=True
        # tf.reduce_mean(feature_map, [1, 2], keep_dims=True)
        # feature_map: (batch, H, W, C) -> (batch, 1, 1, C)
        pooled = torch.mean(feature_map, dim=[1, 2], keepdim=True)  # (batch, 1, 1, C)
        
        # Step 2: transpose with perm=[0, 3, 1, 2]
        # tf.transpose(..., perm=[0, 3, 1, 2])
        # (batch, 1, 1, C) -> (batch, C, 1, 1)
        transpose_feature_map = pooled.permute(0, 3, 1, 2)  # (batch, C, 1, 1)
        
        # Step 3: reshape to [-1, C]
        # tf.reshape(transpose_feature_map, [-1, C])
        reshaped = transpose_feature_map.reshape(-1, self.C)  # (batch, C)
        
        # Step 4: matmul with weight and add bias
        # tf.matmul(..., weight) + bias
        channel_wise_attention_fm = torch.matmul(reshaped, self.weight) + self.bias  # (batch, C)
        
        # Step 5: sigmoid
        # tf.nn.sigmoid(...)
        channel_wise_attention_fm = torch.sigmoid(channel_wise_attention_fm)  # (batch, C)
        
        # Step 6: concat for (H * W) times, then reshape
        # tf.concat([channel_wise_attention_fm] * (H * W), axis=1)
        # Then reshape to [-1, H, W, C]
        # Repeat along axis=1 (which is the second dimension after unsqueeze)
        expanded = channel_wise_attention_fm.unsqueeze(1).expand(-1, self.H * self.W, -1)  # (batch, H*W, C)
        attention = expanded.reshape(batch_size, self.H, self.W, self.C)  # (batch, H, W, C)
        
        # Step 7: multiply with original feature_map
        # attention * feature_map
        attended_fm = attention * feature_map  # (batch, H, W, C)
        
        return attended_fm


class MultiDimensionalAttention(nn.Module):
    """
    Multi-dimensional attention - Exact conversion from TensorFlow disan.py
    """
    def __init__(self, hidden_size, activation='elu'):
        super(MultiDimensionalAttention, self).__init__()
        self.hidden_size = hidden_size
        
        # Two dense layers with batch norm
        self.map1 = nn.Linear(hidden_size, hidden_size)
        self.bn1 = nn.BatchNorm1d(hidden_size)
        self.map2 = nn.Linear(hidden_size, hidden_size)
        self.bn2 = nn.BatchNorm1d(hidden_size)
        
        if activation == 'elu':
            self.activation = nn.ELU()
        elif activation == 'relu':
            self.activation = nn.ReLU()
        else:
            self.activation = nn.Identity()
            
    def forward(self, rep_tensor, rep_mask=None):
        """
        Args:
            rep_tensor: (batch, seq_len, hidden_size)
            rep_mask: optional mask
        Returns:
            output: (batch, hidden_size)
        """
        batch_size, seq_len, hidden_size = rep_tensor.shape
        
        # Reshape for batch norm: (batch*seq_len, hidden_size)
        rep_flat = rep_tensor.reshape(-1, hidden_size)
        
        # First mapping with activation
        map1 = self.map1(rep_flat)
        map1 = self.bn1(map1)
        map1 = self.activation(map1)
        
        # Second mapping (linear)
        map2 = self.map2(map1)
        map2 = self.bn2(map2)
        
        # Reshape back: (batch, seq_len, hidden_size)
        map2 = map2.reshape(batch_size, seq_len, hidden_size)
        
        # Apply mask if provided
        if rep_mask is not None:
            rep_mask = rep_mask.unsqueeze(-1).float()  # (batch, seq_len, 1)
            map2 = map2 * rep_mask + (1 - rep_mask) * (-1e30)
        
        # Softmax over sequence dimension (dim=1)
        # tf.nn.softmax(map2_masked, 1)
        soft = F.softmax(map2, dim=1)  # (batch, seq_len, hidden_size)
        
        # Weighted sum: tf.reduce_sum(soft * rep_tensor, 1)
        attn_output = torch.sum(soft * rep_tensor, dim=1)  # (batch, hidden_size)
        
        return attn_output


class ACRNN(nn.Module):
    """
    ACRNN Model - Exact conversion from TensorFlow
    Architecture: Channel-wise Attention + CNN + LSTM + Self-Attention
    """
    def __init__(self, 
                 input_height=32,      # Number of EEG channels (n_channel)
                 input_width=384,      # Time window length (window_size)
                 input_channels=1,    # Input channels (input_channel_num)
                 conv_channels=40,     # Number of CNN filters (conv_channel_num)
                 kernel_height=32,    # CNN kernel height (kernel_height_1st)
                 kernel_width=45,     # CNN kernel width (kernel_width_1st)
                 pool_height=1,        # Pooling height (pooling_height_1st)
                 pool_width=75,        # Pooling width (pooling_width_1st)
                 pool_stride=10,       # Pooling stride (pooling_stride_1st)
                 lstm_hidden=64,      # LSTM hidden size (n_hidden_state)
                 num_lstm_layers=2,   # Number of LSTM layers
                 num_classes=2,       # Output classes (num_labels)
                 dropout=0.5):        # Dropout probability (dropout_prob)
        super(ACRNN, self).__init__()
        
        self.input_height = input_height  # n_channel
        self.input_width = input_width    # window_size
        self.input_channels = input_channels
        self.conv_channels = conv_channels
        self.lstm_hidden = lstm_hidden
        self.num_lstm_layers = num_lstm_layers
        
        # 1. Channel-wise Attention
        # channel_wise_attention(X_1, 1, window_size, n_channel, ...)
        # H=1, W=window_size, C=n_channel
        self.channel_attention = ChannelWiseAttention(
            H=1, 
            W=input_width, 
            C=input_height,
            weight_decay=0.00004
        )
        
        # 2. CNN Layer
        # cnn_2d.apply_conv2d(conv_1, kernel_height_1st, kernel_width_1st, ...)
        self.conv = nn.Conv2d(
            in_channels=input_channels,
            out_channels=conv_channels,
            kernel_size=(kernel_height, kernel_width),
            stride=1,
            padding=0  # 'VALID' padding in TensorFlow
        )
        self.bn_conv = nn.BatchNorm2d(conv_channels)
        self.relu = nn.ReLU()
        
        # 3. Max Pooling
        # cnn_2d.apply_max_pooling(conv_1, pooling_height_1st, pooling_width_1st, pooling_stride_1st)
        self.pool = nn.MaxPool2d(
            kernel_size=(pool_height, pool_width),
            stride=pool_stride,
            padding=0
        )
        
        # Calculate output size after conv and pooling
        # After conv (VALID padding): 
        #   H_out = H_in - kernel_h + 1
        #   W_out = W_in - kernel_w + 1
        conv_h = input_height - kernel_height + 1
        conv_w = input_width - kernel_width + 1
        
        # After pooling:
        #   H_out = (H_out - pool_h) // stride + 1
        #   W_out = (W_out - pool_w) // stride + 1
        pool_h = (conv_h - pool_height) // pool_stride + 1
        pool_w = (conv_w - pool_width) // pool_stride + 1
        
        # Ensure non-negative dimensions
        pool_h = max(1, pool_h)
        pool_w = max(1, pool_w)
        
        self.pool_output_size = pool_h * pool_w * conv_channels
        
        # 4. Dropout after flattening
        self.dropout1 = nn.Dropout(dropout)
        
        # 5. LSTM Layers
        # BasicLSTMCell with n_hidden_state, 2 layers
        self.lstm = nn.LSTM(
            input_size=self.pool_output_size,
            hidden_size=lstm_hidden,
            num_layers=num_lstm_layers,
            batch_first=True,
            dropout=dropout if num_lstm_layers > 1 else 0,
            bidirectional=False
        )
        
        # 6. Self-Attention (Multi-dimensional Attention)
        # multi_dimensional_attention(rnn_op, 64, ...)
        self.self_attention = MultiDimensionalAttention(
            hidden_size=lstm_hidden,
            activation='elu'
        )
        
        # 7. Dropout after attention
        self.dropout2 = nn.Dropout(dropout)
        
        # 8. Classification Layer (readout)
        # cnn_2d.apply_readout(attention_drop, rnn_op.shape[2].value, num_labels)
        self.classifier = nn.Linear(lstm_hidden, num_classes)
        
    def forward(self, x):
        """
        Args:
            x: Input tensor
               Expected shape from dataset: (batch, time_steps, n_channels)
               Or: (batch, n_channels, time_steps, 1)
        Returns:
            output: (batch, num_classes)
        """
        # Handle input format
        # Original TensorFlow: X shape is (batch, input_height, input_width, input_channel_num)
        # Which is: (batch, n_channel, window_size, 1)
        if x.dim() == 3:
            # Convert from (batch, time_steps, n_channels) to (batch, n_channels, time_steps, 1)
            x = x.permute(0, 2, 1).unsqueeze(-1)  # (batch, n_channels, time_steps, 1)
        
        # Now x is: (batch, n_channels, time_steps, 1) = (batch, input_height, input_width, input_channels)
        # In TensorFlow format: (batch, H, W, C) = (batch, n_channel, window_size, 1)
        
        # Step 1: Transpose as in TensorFlow: tf.transpose(X, [0, 3, 2, 1])
        # (batch, n_channel, window_size, 1) -> (batch, 1, window_size, n_channel)
        # In TensorFlow BHWC format: (batch, H, W, C) = (batch, 1, window_size, n_channel)
        x = x.permute(0, 3, 2, 1)  # (batch, 1, window_size, n_channel) = (batch, H, W, C)
        
        # Step 2: Channel-wise Attention
        # channel_wise_attention(X_1, H=1, W=window_size, C=n_channel, ...)
        # Input is already in (batch, H, W, C) format
        attended = self.channel_attention(x)  # Returns (batch, H, W, C) = (batch, 1, window_size, n_channel)
        
        # Step 3: Transpose back: tf.transpose(conv, [0, 3, 2, 1])
        # (batch, 1, window_size, n_channel) -> (batch, n_channel, window_size, 1)
        # (batch, H, W, C) -> (batch, C, W, H) = (batch, n_channel, window_size, 1)
        conv_1 = attended.permute(0, 3, 2, 1)  # (batch, n_channel, window_size, 1)
        
        # Convert to PyTorch format for CNN: (batch, channels, height, width)
        # (batch, n_channel, window_size, 1) -> (batch, 1, n_channel, window_size)
        conv_1 = conv_1.permute(0, 3, 1, 2)  # (batch, 1, n_channel, window_size)
        
        # Step 4: CNN Layer
        x = self.conv(conv_1)  # (batch, conv_channels, H', W')
        x = self.bn_conv(x)
        x = self.relu(x)
        
        # Step 5: Max Pooling
        x = self.pool(x)  # (batch, conv_channels, H'', W'')
        
        # Step 6: Flatten and Dropout
        batch_size = x.shape[0]
        pool1_flat = x.view(batch_size, -1)  # (batch, pool_output_size)
        fc_drop = self.dropout1(pool1_flat)
        
        # Step 7: Reshape for LSTM
        # tf.reshape(fc_drop, [-1, num_timestep, pool_1_shape[1]*pool_1_shape[2]*pool_1_shape[3]])
        # num_timestep = 1
        lstm_in = fc_drop.unsqueeze(1)  # (batch, 1, pool_output_size)
        
        # Step 8: LSTM
        # tf.nn.dynamic_rnn(lstm_cell, lstm_in, ...)
        rnn_op, _ = self.lstm(lstm_in)  # (batch, 1, lstm_hidden)
        
        # Step 9: Self-Attention
        # multi_dimensional_attention(rnn_op, 64, ...)
        attention_op = self.self_attention(rnn_op)  # (batch, lstm_hidden)
        
        # Step 10: Dropout
        attention_drop = self.dropout2(attention_op)
        
        # Step 11: Classification
        y_ = self.classifier(attention_drop)  # (batch, num_classes)
        
        return y_
